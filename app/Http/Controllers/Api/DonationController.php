<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    private function getFoundation(): Foundation
    {
        return Foundation::where('user_id', auth()->id())->firstOrFail();
    }

    /**
     * Full URL for a donor's profile photo, or null if they have none.
     * Hardcoded base URL matches UserController and the frontend getImage()
     * helpers — change it in one place when moving off localhost.
     */
    private function donorPhotoUrl($donor): ?string
    {
        return $donor && $donor->profile_photo
            ? '/storage/' . $donor->profile_photo
            : null;
    }

    /* ══════════════════════════════
       LIST ALL DONATIONS FOR FOUNDATION
    ══════════════════════════════ */
    public function index(Request $request)
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([]);
        }

        $donations = Donation::with(['donor', 'campaign', 'paymentMethod'])
            ->whereHas('campaign', function ($q) use ($foundation) {
                $q->where('foundation_id', $foundation->id);
            })
            ->latest()
            ->get()
            ->map(function ($d) {
                $donor = $d->donor;
                $campaign = $d->campaign;

                return [
                    'id' => $d->id,
                    'type' => $d->type,
                    'status' => $d->status,

                    // monetary
                    'amount' => $d->amount,
                    'payment_method' => $d->paymentMethod?->name,
                    'proof_photo' => $d->proof_photo,

                    // item
                    'item_name' => $d->item_name,
                    'item_quantity' => $d->item_quantity,
                    'item_description' => $d->item_description,
                    'item_photo' => $d->item_photo,

                    // delivery
                    'delivery_method' => $d->delivery_method,
                    'delivery_address' => $d->delivery_address,

                    'notes' => $d->notes,
                    'donated_at' => $d->donated_at ?? $d->created_at,
                    'created_at' => $d->created_at,

                    'donor' => $donor ? [
                        'id' => $donor->id,
                        'name' => trim($donor->first_name . ' ' . $donor->last_name),
                        'email' => $donor->email,
                        'initials' => strtoupper(
                            substr($donor->first_name ?? '', 0, 1) .
                            substr($donor->last_name ?? '', 0, 1)
                        ),
                        'profile_photo' => $this->donorPhotoUrl($donor),
                    ] : null,

                    'campaign' => $campaign ? [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                        'type' => $campaign->type,
                    ] : null,
                ];
            });

        return response()->json($donations);
    }

    /* ══════════════════════════════
       UPDATE STATUS
    ══════════════════════════════ */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,received,cancelled',
        ]);

        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([
                'message' => 'Your foundation must be verified before managing donations.'
            ], 403);
        }

        $donation = Donation::whereHas('campaign', function ($q) use ($foundation) {
            $q->where('foundation_id', $foundation->id);
        })->with('campaign')->findOrFail($id);

        $donation->update(['status' => $request->status]);

        // Notify the donor when their donation is received or cancelled
        if (in_array($request->status, ['received', 'cancelled'])) {
            Notification::create([
                'user_id' => $donation->donor_id,
                'title' => $request->status === 'received' ? 'Donation Received!' : 'Donation Cancelled',
                'message' => $request->status === 'received'
                    ? "Your donation to \"{$donation->campaign->title}\" has been received."
                    : "Your donation to \"{$donation->campaign->title}\" was cancelled.",
                'type' => $request->status === 'received' ? 'donation_received' : 'donation_cancelled',
                'notifiable_id' => $donation->id,
                'notifiable_type' => Donation::class,
            ]);
        }

        return response()->json([
            'message' => 'Status updated',
            'donation' => $donation,
        ]);
    }

    /* ══════════════════════════════
       DONORS LIST FOR FOUNDATION
    ══════════════════════════════ */
    public function donors()
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([]);
        }

        $donors = Donation::with(['donor', 'campaign'])
            ->whereHas('campaign', function ($q) use ($foundation) {
                $q->where('foundation_id', $foundation->id);
            })
            ->get()
            ->groupBy('donor_id')
            ->map(function ($donations) {
                $donor = $donations->first()->donor;
                $monetary = $donations->where('type', 'monetary');
                $items = $donations->where('type', 'item');

                return [
                    'id' => $donor->id,
                    'name' => trim($donor->first_name . ' ' . $donor->last_name),
                    'email' => $donor->email,
                    'phone' => $donor->phone ?? null,
                    'initials' => strtoupper(
                        substr($donor->first_name ?? '', 0, 1) .
                        substr($donor->last_name ?? '', 0, 1)
                    ),
                    'profile_photo' => $this->donorPhotoUrl($donor),
                    'total_donated' => $monetary->sum('amount'),
                    'total_items' => $items->count(),
                    'total_donations' => $donations->count(),
                    'last_donated' => $donations->sortByDesc('created_at')->first()->created_at,
                    'campaigns' => $donations->pluck('campaign.title')->filter()->unique()->values(),
                    'status' => $donor->status ?? 'active',
                ];
            })
            ->values();

        return response()->json($donors);
    }

    /* ══════════════════════════════
       REPORTS
    ══════════════════════════════ */
    public function reports(Request $request)
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([
                'summary' => [
                    'total_raised' => 0,
                    'total_items' => 0,
                    'total_donors' => 0,
                    'total_donations' => 0,
                    'total_campaigns' => 0,
                    'active_campaigns' => 0,
                ],
                'monthly' => [],
                'per_campaign' => [],
            ]);
        }

        $donations = Donation::with(['donor', 'campaign'])
            ->whereHas('campaign', function ($q) use ($foundation) {
                $q->where('foundation_id', $foundation->id);
            })
            ->get();

        $campaigns = Campaign::where('foundation_id', $foundation->id)->get();

        // Monthly breakdown (last 6 months)
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $label = $month->format('M Y');
            $monthKey = $month->format('Y-m');

            $monthDonations = $donations->filter(
                fn($d) => Carbon::parse($d->created_at)->format('Y-m') === $monthKey
            );

            $monthly[] = [
                'month' => $label,
                'monetary' => $monthDonations->where('type', 'monetary')->sum('amount'),
                'items' => $monthDonations->where('type', 'item')->count(),
                'total' => $monthDonations->count(),
            ];
        }

        // Per-campaign breakdown
        $perCampaign = $campaigns->map(function ($c) use ($donations) {
            $cd = $donations->where('campaign_id', $c->id);

            return [
                'id' => $c->id,
                'title' => $c->title,
                'status' => $c->status,
                'type' => $c->type,
                'goal_amount' => $c->goal_amount,
                'raised' => $cd->where('type', 'monetary')->sum('amount'),
                'items' => $cd->where('type', 'item')->count(),
                'donors' => $cd->pluck('donor_id')->unique()->count(),
                'percent' => $c->goal_amount > 0
                    ? min(round(($c->current_amount / $c->goal_amount) * 100), 100)
                    : 0,
            ];
        });

        return response()->json([
            'summary' => [
                'total_raised' => $donations->where('type', 'monetary')->sum('amount'),
                'total_items' => $donations->where('type', 'item')->count(),
                'total_donors' => $donations->pluck('donor_id')->unique()->count(),
                'total_donations' => $donations->count(),
                'total_campaigns' => $campaigns->count(),
                'active_campaigns' => $campaigns->where('status', 'active')->count(),
            ],
            'monthly' => $monthly,
            'per_campaign' => $perCampaign->values(),
        ]);
    }

    /* ══════════════════════════════
       DASHBOARD
    ══════════════════════════════ */
    public function dashboard()
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([
                'total_monetary' => 0,
                'total_items' => 0,
                'total_donors' => 0,
                'pending' => 0,
            ]);
        }

        $donations = Donation::whereHas('campaign', function ($q) use ($foundation) {
            $q->where('foundation_id', $foundation->id);
        })->get();

        return response()->json([
            'total_monetary' => $donations->where('type', 'monetary')->sum('amount'),
            'total_items' => $donations->where('type', 'item')->count(),
            'total_donors' => $donations->pluck('donor_id')->unique()->count(),
            'pending' => $donations->where('status', 'pending')->count(),
        ]);
    }
}