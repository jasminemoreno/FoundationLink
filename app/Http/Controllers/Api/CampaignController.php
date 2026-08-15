<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignPhoto;
use App\Models\Foundation;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    private function getFoundation()
    {
        return Foundation::where('user_id', auth()->id())->firstOrFail();
    }

    /* ══════════════════════════════
       LIST
    ══════════════════════════════ */
    public function index()
    {
        $foundation = $this->getFoundation();

        $campaigns = Campaign::with(['photos', 'category'])
            ->where('foundation_id', $foundation->id)
            ->latest()
            ->get();

        return response()->json($campaigns);
    }

    /* ══════════════════════════════
       CREATE
    ══════════════════════════════ */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:monetary,item,both',
            'goal_amount' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'cover_photo' => 'sometimes|nullable|image|max:4096',
            'photos.*' => 'sometimes|nullable|image|max:4096',
            'captions.*' => 'sometimes|nullable|string|max:255',
            'accepted_delivery_methods' => 'nullable|array',
            'accepted_delivery_methods.*' => 'in:pickup,dropoff',
            'accepted_payment_methods' => 'nullable|array',
            'accepted_payment_methods.*' => 'integer|exists:payment_methods,id',
        ]);
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([
                'message' => 'Your foundation must be verified by the super admin before you can create campaigns.'
            ], 403);
        }

        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $request->file('cover_photo')->store('campaigns/covers', 'public');
        }

        $deliveryMethods = [];
        if (in_array($validated['type'], ['item', 'both'])) {
            $deliveryMethods = $request->input('accepted_delivery_methods', []);
        }

        $paymentMethods = [];
        if (in_array($validated['type'], ['monetary', 'both'])) {
            $paymentMethods = $request->input('accepted_payment_methods', []);
        }

        $campaign = Campaign::create([
            'foundation_id' => $foundation->id,
            'category_id' => $validated['category_id'] ?? null,
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'type' => $validated['type'],
            'goal_amount' => $validated['goal_amount'] ?? 0,
            'current_amount' => 0,
            'cover_photo' => $coverPath,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'active',
            'accepted_delivery_methods' => $deliveryMethods,
            'accepted_payment_methods' => $paymentMethods,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $i => $photo) {
                $path = $photo->store('campaigns/photos', 'public');
                CampaignPhoto::create([
                    'campaign_id' => $campaign->id,
                    'photo_path' => $path,
                    'caption' => $request->captions[$i] ?? null,
                    'order' => $i,
                ]);
            }
        }

        $this->notifyFollowers($foundation, $campaign);

        return response()->json([
            'message' => 'Campaign created successfully',
            'campaign' => $campaign->load(['photos', 'category']),
        ], 201);
    }

    /* ══════════════════════════════
       UPDATE
    ══════════════════════════════ */
    public function update(Request $request, $id)
    {
        $foundation = $this->getFoundation();
        $campaign = Campaign::where('id', $id)
            ->where('foundation_id', $foundation->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'sometimes|in:monetary,item,both',
            'goal_amount' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:draft,active,completed,cancelled,paused',
            'cover_photo' => 'sometimes|nullable|image|max:4096',
            'photos.*' => 'sometimes|nullable|image|max:4096',
            'captions.*' => 'sometimes|nullable|string|max:255',
            'delete_photos' => 'nullable|array',
            'delete_photos.*' => 'integer',
            'accepted_delivery_methods' => 'nullable|array',
            'accepted_delivery_methods.*' => 'in:pickup,dropoff',
            'accepted_payment_methods' => 'nullable|array',
            'accepted_payment_methods.*' => 'integer|exists:payment_methods,id',
        ]);

        if ($request->hasFile('cover_photo')) {
            if ($campaign->cover_photo) {
                Storage::disk('public')->delete($campaign->cover_photo);
            }
            $validated['cover_photo'] = $request->file('cover_photo')->store('campaigns/covers', 'public');
        }

        $type = $validated['type'] ?? $campaign->type;

        $validated['accepted_delivery_methods'] = in_array($type, ['item', 'both'])
            ? $request->input('accepted_delivery_methods', [])
            : [];

        $validated['accepted_payment_methods'] = in_array($type, ['monetary', 'both'])
            ? $request->input('accepted_payment_methods', [])
            : [];

        $campaign->update($validated);

        if (!empty($validated['delete_photos'])) {
            $toDelete = CampaignPhoto::whereIn('id', $validated['delete_photos'])
                ->where('campaign_id', $campaign->id)->get();
            foreach ($toDelete as $p) {
                Storage::disk('public')->delete($p->photo_path);
                $p->delete();
            }
        }

        if ($request->hasFile('photos')) {
            $offset = $campaign->photos()->count();
            foreach ($request->file('photos') as $i => $photo) {
                $path = $photo->store('campaigns/photos', 'public');
                CampaignPhoto::create([
                    'campaign_id' => $campaign->id,
                    'photo_path' => $path,
                    'caption' => $request->captions[$i] ?? null,
                    'order' => $offset + $i,
                ]);
            }
        }

        return response()->json([
            'message' => 'Campaign updated successfully',
            'campaign' => $campaign->load(['photos', 'category']),
        ]);
    }

    /* ══════════════════════════════
       PAUSE
    ══════════════════════════════ */
    public function pause(Request $request, $id)
    {
        $request->validate(['pause_reason' => 'required|string|max:500']);

        $foundation = $this->getFoundation();
        $campaign = Campaign::where('id', $id)->where('foundation_id', $foundation->id)->firstOrFail();

        if ($campaign->status !== 'active') {
            return response()->json(['message' => 'Only active campaigns can be paused.'], 422);
        }

        $campaign->update(['status' => 'paused', 'pause_reason' => $request->pause_reason]);
        return response()->json(['message' => 'Campaign paused', 'campaign' => $campaign]);
    }

    /* ══════════════════════════════
       RESUME
    ══════════════════════════════ */
    public function resume($id)
    {
        $foundation = $this->getFoundation();
        $campaign = Campaign::where('id', $id)->where('foundation_id', $foundation->id)->firstOrFail();

        if ($campaign->status !== 'paused') {
            return response()->json(['message' => 'Only paused campaigns can be resumed.'], 422);
        }

        $campaign->update(['status' => 'active', 'pause_reason' => null]);
        return response()->json(['message' => 'Campaign resumed', 'campaign' => $campaign]);
    }

    /* ══════════════════════════════
       COMPLETE
    ══════════════════════════════ */
    public function complete($id)
    {
        $foundation = $this->getFoundation();
        $campaign = Campaign::where('id', $id)->where('foundation_id', $foundation->id)->firstOrFail();

        if (!in_array($campaign->status, ['active', 'paused'])) {
            return response()->json(['message' => 'Campaign cannot be completed.'], 422);
        }

        $campaign->update(['status' => 'completed', 'pause_reason' => null]);
        return response()->json(['message' => 'Campaign marked as completed', 'campaign' => $campaign]);
    }

    /* ══════════════════════════════
       DELETE
    ══════════════════════════════ */
    public function destroy($id)
    {
        $foundation = $this->getFoundation();
        $campaign = Campaign::where('id', $id)->where('foundation_id', $foundation->id)->firstOrFail();

        foreach ($campaign->photos as $p) {
            Storage::disk('public')->delete($p->photo_path);
            $p->delete();
        }

        if ($campaign->cover_photo) {
            Storage::disk('public')->delete($campaign->cover_photo);
        }

        $campaign->delete();
        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    /* ══════════════════════════════
       HELPERS
    ══════════════════════════════ */
    private function notifyFollowers(Foundation $foundation, Campaign $campaign)
    {
        $followerIds = DB::table('foundation_followers')
            ->where('foundation_id', $foundation->id)
            ->where('type', 'follow')
            ->pluck('donor_id');

        foreach ($followerIds as $donorId) {
            Notification::create([
                'user_id' => $donorId,
                'title' => $foundation->name . ' launched a new campaign',
                'message' => $campaign->title,
                'type' => 'new_campaign',
                'notifiable_id' => $campaign->id,
                'notifiable_type' => Campaign::class,
            ]);
        }
    }
}