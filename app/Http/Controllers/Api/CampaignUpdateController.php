<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\User;
use App\Models\UpdateReaction;
use Illuminate\Http\Request;

class CampaignUpdateController extends Controller
{
    /* ══════════════════════════════
       FOUNDATION ADMIN: LIST UPDATES FOR A CAMPAIGN
    ══════════════════════════════ */
    public function index($campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('foundation_id', $this->getFoundationId())
            ->firstOrFail();

        $updates = $campaign->updates()
            ->withCount('reactions')
            ->latest()
            ->get();

        return response()->json($updates);
    }

    /* ══════════════════════════════
       FOUNDATION ADMIN: POST AN UPDATE
    ══════════════════════════════ */
    public function store(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('foundation_id', $this->getFoundationId())
            ->firstOrFail();

        if ($campaign->status !== 'completed') {
            return response()->json([
                'message' => 'Updates can only be posted on completed campaigns.'
            ], 422);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:4096',
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('campaigns/updates', 'public');
            }
        }

        $update = CampaignUpdate::create([
            'campaign_id' => $campaign->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'photos' => $photoPaths,
        ]);

        $this->notifyDonors($campaign, $update);

        return response()->json([
            'message' => 'Update posted and donors notified.',
            'update' => $update,
        ], 201);
    }

    /* ══════════════════════════════
       FOUNDATION ADMIN: EDIT AN UPDATE
    ══════════════════════════════ */
    public function update(Request $request, $campaignId, $updateId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('foundation_id', $this->getFoundationId())
            ->firstOrFail();

        $update = CampaignUpdate::where('id', $updateId)
            ->where('campaign_id', $campaign->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:4096',
            'existing_photos' => 'nullable|array',
            'existing_photos.*' => 'string',
        ]);

        $photoPaths = $validated['existing_photos'] ?? ($update->photos ?? []);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photoPaths[] = $photo->store('campaigns/updates', 'public');
            }
        }

        $update->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'photos' => $photoPaths,
        ]);

        return response()->json([
            'message' => 'Update edited successfully.',
            'update' => $update,
        ]);
    }

    /* ══════════════════════════════
       FOUNDATION ADMIN: DELETE AN UPDATE
    ══════════════════════════════ */
    public function destroy($campaignId, $updateId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('foundation_id', $this->getFoundationId())
            ->firstOrFail();

        $update = CampaignUpdate::where('id', $updateId)
            ->where('campaign_id', $campaign->id)
            ->firstOrFail();

        $update->delete();

        return response()->json(['message' => 'Update deleted.']);
    }

    /* ══════════════════════════════
       DONOR: LIST UPDATES FOR A CAMPAIGN THEY DONATED TO
    ══════════════════════════════ */
    public function donorIndex($campaignId)
    {
        $hasDonated = Donation::where('campaign_id', $campaignId)
            ->where('donor_id', auth()->id())
            ->exists();

        if (!$hasDonated) {
            return response()->json(['message' => 'Not authorized.'], 403);
        }

        $updates = CampaignUpdate::where('campaign_id', $campaignId)
            ->with('reactions')
            ->latest()
            ->get();

        $userId = auth()->id();

        $updates = $updates->map(function ($u) use ($userId) {
            $u->total_reactions = $u->reactions->count();
            $u->my_reaction = $u->reactions->contains('donor_id', $userId);
            unset($u->reactions);
            return $u;
        });

        return response()->json($updates);
    }

    /* ══════════════════════════════
       DONOR: TOGGLE HEART REACTION ON AN UPDATE
    ══════════════════════════════ */
    public function react($campaignId, $updateId)
    {
        $donor = auth()->user();

        // must belong to a campaign the donor donated to, or a foundation they follow
        $update = CampaignUpdate::where('id', $updateId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        $existing = UpdateReaction::where('donor_id', $donor->id)
            ->where('campaign_update_id', $update->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $reacted = false;
        } else {
            UpdateReaction::create([
                'donor_id' => $donor->id,
                'campaign_update_id' => $update->id,
            ]);
            $reacted = true;
        }

        return response()->json([
            'reacted' => $reacted,
            'total_reactions' => $update->reactions()->count(),
        ]);
    }

    /* ══════════════════════════════
       HELPERS
    ══════════════════════════════ */
    private function getFoundationId()
    {
        return Foundation::where('user_id', auth()->id())->firstOrFail()->id;
    }

    private function notifyDonors(Campaign $campaign, CampaignUpdate $update)
    {
        $donorIds = Donation::where('campaign_id', $campaign->id)
            ->where('status', 'received')
            ->pluck('donor_id')
            ->unique();

        $donors = User::whereIn('id', $donorIds)->get();

        foreach ($donors as $donor) {
            Notification::create([
                'user_id' => $donor->id,
                'title' => 'Campaign Update: ' . $campaign->title,
                'message' => $update->title,
                'type' => 'campaign_update',
                'notifiable_id' => $update->id,
                'notifiable_type' => CampaignUpdate::class,
            ]);
        }
    }
    /* ══════════════════════════════
   FOUNDATION ADMIN: LIST DONORS WHO REACTED TO AN UPDATE
══════════════════════════════ */
    public function reactors($campaignId, $updateId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('foundation_id', $this->getFoundationId())
            ->firstOrFail();

        $update = CampaignUpdate::where('id', $updateId)
            ->where('campaign_id', $campaign->id)
            ->firstOrFail();

        $donors = $update->reactions()
            ->with('donor:id,first_name,last_name,profile_photo')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->donor->id,
                'name' => $r->donor->first_name . ' ' . $r->donor->last_name,
                'profile_photo' => $r->donor->profile_photo,
                'reacted_at' => $r->created_at->diffForHumans(),
            ]);

        return response()->json([
            'total' => $donors->count(),
            'donors' => $donors,
        ]);
    }
}