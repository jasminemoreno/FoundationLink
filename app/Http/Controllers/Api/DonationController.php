<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Support\PrivateFile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                    'proof_photo' => PrivateFile::url($d->proof_photo),

                    // item
                    'item_name' => $d->item_name,
                    'item_quantity' => $d->item_quantity,
                    'item_description' => $d->item_description,
                    'item_photo' => $d->item_photo,

                    // delivery
                    'delivery_method' => $d->delivery_method,
                    'delivery_address' => $d->delivery_address,
                    // exact pick-up spot the donor shared, for "Get directions"
                    'pickup_latitude' => $d->pickup_latitude,
                    'pickup_longitude' => $d->pickup_longitude,
                    'pickup_accuracy' => $d->pickup_accuracy,
                    'pickup_schedule' => $d->pickupSchedule(),

                    'notes' => $d->notes,
                    'thank_you_note' => $d->thank_you_note,
                    'donated_at' => $d->donated_at ?? $d->created_at,
                    'created_at' => $d->created_at,
                    // when it was last changed (e.g. marked received), for the details history
                    'updated_at' => $d->updated_at,

                    'donor' => $donor ? [
                        'id' => $donor->id,
                        'name' => trim($donor->first_name . ' ' . $donor->last_name),
                        'email' => $donor->email,
                        // the driver may need to call; shared only for pick-ups
                        'phone' => $d->delivery_method === 'pickup' ? $donor->phone : null,
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
       PICK-UP SCHEDULE
       The day (morning / afternoon / exact time) the foundation will collect
       a pending Pick Up item donation. Setting or changing it clears the
       donor's previous answer and tells the donor.
    ══════════════════════════════ */
    public function schedulePickup(Request $request, $id)
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([
                'message' => 'Your foundation must be verified before managing donations.'
            ], 403);
        }

        $donation = Donation::with('campaign')
            ->whereHas('campaign', fn($q) => $q->where('foundation_id', $foundation->id))
            ->findOrFail($id);

        // Wall-clock dates in the Philippines, from today up to 90 days ahead
        $today = now(config('app.display_timezone'));
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today->toDateString(),
                'before_or_equal:' . $today->copy()->addDays(90)->toDateString()],
            'window' => 'required|in:morning,afternoon,exact',
            'time' => 'nullable|required_if:window,exact|date_format:H:i',
            'note' => 'nullable|string|max:300',
        ]);

        if ($donation->type !== 'item' || $donation->delivery_method !== 'pickup') {
            return response()->json(['message' => 'Only pick-up item donations can be scheduled.'], 422);
        }
        if ($donation->status !== 'pending') {
            return response()->json(['message' => 'Only pending donations can be scheduled.'], 422);
        }

        $changed = (bool) $donation->pickup_date;
        $note = trim((string) ($data['note'] ?? '')) ?: null;

        $donation->update([
            'pickup_date' => $data['date'],
            'pickup_window' => $data['window'],
            'pickup_time' => $data['window'] === 'exact' ? $data['time'] : null,
            'pickup_schedule_note' => $note,
            // a new date needs a new answer from the donor
            'pickup_confirmed_at' => null,
            'pickup_request_date' => null,
            'pickup_request_window' => null,
            'pickup_request_note' => null,
        ]);

        $schedule = $donation->fresh()->pickupSchedule();

        Notification::create([
            'user_id' => $donation->donor_id,
            'type' => 'pickup_scheduled',
            'title' => $changed ? 'Pick-up schedule changed' : 'Pick-up scheduled',
            'message' => "{$foundation->name} will pick up your {$donation->item_quantity}x {$donation->item_name} on {$schedule['label']}."
                . ($note ? " Note: \"{$note}\"" : '')
                . ' Please confirm or ask for another day.',
            'notifiable_id' => $donation->id,
            'notifiable_type' => Donation::class,
        ]);

        return response()->json([
            'message' => $changed ? 'Pick-up schedule changed. The donor was notified.' : 'Pick-up scheduled. The donor was notified.',
            'pickup_schedule' => $schedule,
        ]);
    }

    /* ══════════════════════════════
       UPDATE STATUS
    ══════════════════════════════ */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,received,cancelled',
            // optional personal message to the donor, only when marking received
            'thank_you_note' => 'nullable|string|max:500',
        ], [
            'thank_you_note.max' => 'The thank-you note can be at most 500 characters.',
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

        $wasReceived = $donation->status === 'received';
        $isReceived = $request->status === 'received';

        $thankYou = $isReceived ? (trim((string) $request->thank_you_note) ?: null) : null;

        DB::transaction(function () use ($donation, $request, $wasReceived, $isReceived, $thankYou) {
            $donation->update(['status' => $request->status] + ($isReceived ? ['thank_you_note' => $thankYou] : []));

            // campaigns.current_amount counts only confirmed money, so move it
            // exactly when a monetary donation enters or leaves 'received'.
            if ($donation->type === 'monetary' && $wasReceived !== $isReceived) {
                $isReceived
                    ? $donation->campaign->increment('current_amount', $donation->amount)
                    : $donation->campaign->decrement('current_amount', $donation->amount);
            }
        });

        // Notify the donor when their donation is received or cancelled
        if (in_array($request->status, ['received', 'cancelled'])) {
            Notification::create([
                'user_id' => $donation->donor_id,
                'title' => $request->status === 'received' ? 'Donation Received!' : 'Donation Cancelled',
                'message' => $request->status === 'received'
                    ? "Your donation to \"{$donation->campaign->title}\" has been received."
                        . ($thankYou ? " {$foundation->name} says: \"{$thankYou}\"" : '')
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
    /**
     * Who follows this foundation: name and photo only (never email, phone or
     * address), when they followed, and whether they have donated to it.
     * Plus numbers for the Reports page: totals and follows per month.
     */
    public function followers()
    {
        $foundation = Foundation::where('user_id', auth()->id())->first();
        $tz = config('app.display_timezone');

        $rows = $foundation
            ? DB::table('foundation_followers')
                ->join('users', 'users.id', '=', 'foundation_followers.donor_id')
                ->where('foundation_followers.foundation_id', $foundation->id)
                ->where('foundation_followers.type', 'follow')
                ->orderByDesc('foundation_followers.created_at')
                ->get(['users.id', 'users.first_name', 'users.last_name', 'users.profile_photo', 'foundation_followers.created_at as followed_at'])
            : collect();

        // donations to THIS foundation that weren't cancelled, per follower
        $donated = $foundation
            ? Donation::whereIn('donor_id', $rows->pluck('id'))
                ->where('status', '!=', 'cancelled')
                ->whereHas('campaign', fn($q) => $q->where('foundation_id', $foundation->id))
                ->selectRaw('donor_id, COUNT(*) as n')
                ->groupBy('donor_id')
                ->pluck('n', 'donor_id')
            : collect();

        $followers = $rows->map(fn($r) => [
            'id' => $r->id,
            'name' => trim($r->first_name . ' ' . $r->last_name),
            'initials' => strtoupper(substr($r->first_name ?? '', 0, 1) . substr($r->last_name ?? '', 0, 1)),
            'profile_photo' => $r->profile_photo ? '/storage/' . $r->profile_photo : null,
            'followed_at' => Carbon::parse($r->followed_at)->toIso8601String(),
            'has_donated' => ($donated[$r->id] ?? 0) > 0,
            'donations_count' => (int) ($donated[$r->id] ?? 0),
        ])->values();

        $thisMonth = now($tz)->format('Y-m');
        $monthOf = fn($f) => Carbon::parse($f['followed_at'])->setTimezone($tz)->format('Y-m');
        $byMonth = collect(range(5, 0))->map(function ($ago) use ($tz, $followers, $monthOf) {
            $month = now($tz)->startOfMonth()->subMonths($ago);
            return [
                'month' => $month->format('Y-m'),
                'label' => $month->format('M'),
                'count' => $followers->filter(fn($f) => $monthOf($f) === $month->format('Y-m'))->count(),
            ];
        })->values();

        $total = $followers->count();
        $donors = $followers->where('has_donated', true)->count();

        return response()->json([
            'summary' => [
                'total' => $total,
                'donated' => $donors,
                'new_this_month' => $followers->filter(fn($f) => $monthOf($f) === $thisMonth)->count(),
                'became_donors_percent' => $total ? (int) round($donors / $total * 100) : 0,
            ],
            'by_month' => $byMonth,
            'followers' => $followers,
        ]);
    }

    public function donors()
    {
        $foundation = $this->getFoundation();

        if ($foundation->status !== 'verified') {
            return response()->json([]);
        }

        // donors who follow this foundation (one query for the whole list)
        $followerIds = DB::table('foundation_followers')
            ->where('foundation_id', $foundation->id)
            ->where('type', 'follow')
            ->pluck('donor_id')
            ->flip();

        $donors = Donation::with(['donor', 'campaign'])
            ->whereHas('campaign', function ($q) use ($foundation) {
                $q->where('foundation_id', $foundation->id);
            })
            ->get()
            ->groupBy('donor_id')
            ->map(function ($donations) use ($followerIds) {
                $donor = $donations->first()->donor;
                $received = $donations->where('status', 'received');
                $monetary = $received->where('type', 'monetary');
                $items = $received->where('type', 'item');

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
                    'first_donated' => $donations->min(fn($d) => $d->donated_at ?? $d->created_at),
                    'pending_count' => $donations->where('status', 'pending')->count(),
                    'is_follower' => isset($followerIds[$donor->id]),
                    'campaigns' => $donations->pluck('campaign.title')->filter()->unique()->values(),
                    // latest donations to this foundation, for Donor Details
                    // newest first by the date shown (donated_at), id breaks ties
                    'recent' => $donations
                        ->sortByDesc(fn($d) => [($d->donated_at ?? $d->created_at)->timestamp, $d->id])
                        ->take(5)->map(fn($d) => [
                        'id' => $d->id,
                        'type' => $d->type,
                        'status' => $d->status,
                        'amount' => $d->amount,
                        'item_name' => $d->item_name,
                        'item_quantity' => $d->item_quantity,
                        'campaign' => $d->campaign?->title,
                        'donated_at' => $d->donated_at ?? $d->created_at,
                    ])->values(),
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

        // Raised / items / donor totals count only what the foundation confirmed;
        // pending and cancelled donations are not money or goods actually received.
        $received = $donations->where('status', 'received');

        // Monthly breakdown (last 6 months)
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $label = $month->format('M Y');
            $monthKey = $month->format('Y-m');

            $monthDonations = $received->filter(
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
        $perCampaign = $campaigns->map(function ($c) use ($received) {
            $cd = $received->where('campaign_id', $c->id);

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
                'total_raised' => $received->where('type', 'monetary')->sum('amount'),
                'total_items' => $received->where('type', 'item')->count(),
                'total_donors' => $received->pluck('donor_id')->unique()->count(),
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

        $received = $donations->where('status', 'received');

        return response()->json([
            'total_monetary' => $received->where('type', 'monetary')->sum('amount'),
            'total_items' => $received->where('type', 'item')->count(),
            'total_donors' => $received->pluck('donor_id')->unique()->count(),
            'pending' => $donations->where('status', 'pending')->count(),
        ]);
    }
}