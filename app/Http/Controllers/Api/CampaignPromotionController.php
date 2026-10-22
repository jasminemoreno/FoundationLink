<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignPromotion;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Foundation admin: promote a running campaign inside the app, and see a
 * per-campaign timeline (donations per day / month with the promotions on it)
 * on the Reports page.
 */
class CampaignPromotionController extends Controller
{
    /** Days shown in the per-day chart / months in the per-month chart, at most. */
    private const MAX_DAYS = 60;
    private const MAX_MONTHS = 24;

    private function ownFoundation(): Foundation
    {
        return Foundation::where('user_id', auth()->id())->firstOrFail();
    }

    private function ownCampaign(Foundation $foundation, $id): Campaign
    {
        return Campaign::where('id', $id)->where('foundation_id', $foundation->id)->firstOrFail();
    }

    /* ══════════════════════════════
       READY-MADE MESSAGES
       One per promotion type, filled in with the campaign's real numbers.
       The foundation can still edit the text before promoting.
    ══════════════════════════════ */
    public function suggestions($id)
    {
        $campaign = $this->ownCampaign($this->ownFoundation(), $id);
        $peso = fn($v) => '₱' . number_format((float) $v);
        $goal = (float) $campaign->goal_amount;
        $raised = (float) $campaign->current_amount;
        $hasGoal = $goal > 0 && $campaign->type !== 'item';
        $daysLeft = $campaign->daysLeft();
        // Leave room for the rest of the sentence within the 200-character limit
        $title = Str::limit($campaign->title, 80);

        $urgent = $hasGoal && $raised < $goal
            ? "We still need {$peso($goal - $raised)} for {$title}. Every peso helps!"
            : "{$title} still needs your help. Every donation counts!";

        $deadline = match (true) {
            $daysLeft === null => null,
            $daysLeft === 0 => "Today is the last day to support {$title}!" . ($hasGoal ? " Help us reach our {$peso($goal)} goal." : ''),
            default => "Only {$daysLeft} " . Str::plural('day', $daysLeft) . " left"
                . ($hasGoal ? " to reach our {$peso($goal)} goal" : '') . " for {$title}!",
        };

        $pct = $hasGoal ? (int) min(100, floor($raised / $goal * 100)) : 0;
        $milestone = $pct > 0
            ? "We're {$pct}% there! Help us reach {$peso($goal)} for {$title}."
            : "Be one of the first to support {$title} and help us reach {$peso($goal)}!";

        $types = [
            ['type' => 'urgent', 'available' => true, 'reason' => null, 'message' => $urgent],
            [
                'type' => 'deadline',
                'available' => $deadline !== null,
                'reason' => $deadline === null ? 'Add a future end date to the campaign to use this.' : null,
                'message' => $deadline ?? '',
            ],
            [
                'type' => 'milestone',
                'available' => $hasGoal,
                'reason' => $hasGoal ? null : 'Needs a money goal to show progress.',
                'message' => $hasGoal ? $milestone : '',
            ],
        ];

        return response()->json([
            'types' => array_map(fn($t) => $t + [
                'label' => CampaignPromotion::TYPES[$t['type']],
                'message' => Str::limit($t['message'], 200, ''),
            ], $types),
        ]);
    }

    /* ══════════════════════════════
       PROMOTE A CAMPAIGN
       Shown first to donors with a "Promoted" label for the chosen days,
       and announced to the foundation's followers. One at a time.
    ══════════════════════════════ */
    public function promote(Request $request, $id)
    {
        $validated = $request->validate([
            // Older clients send no type: treated as "urgent need"
            'type' => 'nullable|string|in:' . implode(',', array_keys(CampaignPromotion::TYPES)),
            'message' => 'required|string|max:200',
            'duration_days' => 'required|integer|in:' . implode(',', CampaignPromotion::DURATIONS),
        ], [
            'message.required' => 'Please write a short message for your followers.',
            'duration_days.in' => 'Please choose 3, 7 or 14 days.',
        ]);

        $foundation = $this->ownFoundation();
        abort_if(
            $foundation->status !== 'verified',
            403,
            'Your foundation must be verified by the super admin to promote campaigns.'
        );
        $campaign = $this->ownCampaign($foundation, $id);

        if ($campaign->status !== 'active') {
            return response()->json(['message' => 'Only running (active) campaigns can be promoted.'], 422);
        }

        $type = $validated['type'] ?? 'urgent';
        if ($type === 'deadline' && $campaign->daysLeft() === null) {
            return response()->json([
                'message' => 'A deadline promotion needs the campaign to have a future end date.',
            ], 422);
        }

        $running = $campaign->promotions()->running()->first();
        if ($running) {
            return response()->json([
                'message' => 'This campaign is already being promoted until '
                    . $running->ends_at->format('M j, Y g:i A') . '.',
            ], 422);
        }

        $promotion = DB::transaction(function () use ($campaign, $foundation, $validated, $type) {
            $promotion = CampaignPromotion::create([
                'campaign_id' => $campaign->id,
                'created_by' => auth()->id(),
                'type' => $type,
                'message' => trim($validated['message']),
                'duration_days' => $validated['duration_days'],
                'starts_at' => now(),
                'ends_at' => now()->addDays($validated['duration_days']),
            ]);

            // Tell the foundation's followers (people who only "liked" are not messaged)
            $followerIds = DB::table('foundation_followers')
                ->where('foundation_id', $foundation->id)
                ->where('type', 'follow')
                ->pluck('donor_id');

            foreach ($followerIds as $donorId) {
                Notification::create([
                    'user_id' => $donorId,
                    'title' => "{$foundation->name} needs your help",
                    'message' => "{$campaign->title}: {$promotion->message}",
                    'type' => 'campaign_promotion',
                    'notifiable_id' => $campaign->id,
                    'notifiable_type' => Campaign::class,
                ]);
            }

            $promotion->update(['notified_count' => $followerIds->count()]);

            return $promotion;
        });

        return response()->json([
            'message' => "Campaign promoted for {$promotion->duration_days} days.",
            'promotion' => $this->formatPromotion($promotion),
        ], 201);
    }

    /* ══════════════════════════════
       TIMELINE (Reports page)
       ?group=day   -> received donations per day (up to the last 60 days)
       ?group=month -> per month (up to the last 24 months)
       plus the campaign's promotions (drawn on the chart) and a list of
       everything that happened, newest first.
    ══════════════════════════════ */
    public function timeline(Request $request, $id)
    {
        $foundation = $this->ownFoundation();
        $campaign = $this->ownCampaign($foundation, $id);
        $group = $request->query('group') === 'month' ? 'month' : 'day';

        $donations = Donation::with(['donor', 'paymentMethod'])
            ->where('campaign_id', $campaign->id)
            ->get();
        $promotions = $campaign->promotions()->get();
        $updates = CampaignUpdate::where('campaign_id', $campaign->id)->get();

        // Days and months are counted in the users' local time (Philippines by
        // default), not UTC, so a 7 AM donation isn't shown on the previous day.
        $tz = config('app.display_timezone');
        $local = fn($time) => Carbon::parse($time)->setTimezone($tz);

        // When a donation happened (donated_at, or created_at for older rows)
        $when = fn($d) => $local($d->donated_at ?? $d->created_at);

        /* ── Chart buckets: every day / month from the campaign's start to now ── */
        $today = now($tz);
        if ($group === 'day') {
            $start = $local($campaign->created_at)->startOfDay()
                ->max($today->copy()->subDays(self::MAX_DAYS - 1)->startOfDay());
            $keyOf = fn(Carbon $t) => $t->format('Y-m-d');
            $labelOf = fn(Carbon $t) => $t->format('M j');
            $step = fn(Carbon $t) => $t->addDay();
        } else {
            $start = $local($campaign->created_at)->startOfMonth()
                ->max($today->copy()->subMonthsNoOverflow(self::MAX_MONTHS - 1)->startOfMonth());
            $keyOf = fn(Carbon $t) => $t->format('Y-m');
            $labelOf = fn(Carbon $t) => $t->format('M Y');
            $step = fn(Carbon $t) => $t->addMonthNoOverflow();
        }

        // Only donations the foundation confirmed count as money / items received
        $received = $donations->where('status', 'received')->groupBy(fn($d) => $keyOf($when($d)));

        $buckets = [];
        for ($t = $start->copy(); $keyOf($t) <= $keyOf($today); $step($t)) {
            $inBucket = $received->get($keyOf($t), collect());
            $buckets[] = [
                'key' => $keyOf($t),
                'label' => $labelOf($t),
                'amount' => (float) $inBucket->where('type', 'monetary')->sum('amount'),
                'items' => $inBucket->where('type', 'item')->count(),
                'count' => $inBucket->count(), // received donations (money + items)
                'donors' => $inBucket->pluck('donor_id')->unique()->count(),
            ];
        }

        /* ── Everything that happened, newest first ── */
        $donorName = fn($d) => $d->donor
            ? trim($d->donor->first_name . ' ' . $d->donor->last_name)
            : 'A donor';

        $events = collect();
        foreach ($donations as $d) {
            $events->push([
                'type' => 'donation',
                'at' => $when($d)->toIso8601String(),
                'title' => $d->type === 'monetary'
                    ? "{$donorName($d)} donated ₱" . number_format((float) $d->amount, 2)
                    : "{$donorName($d)} donated {$d->item_quantity}× {$d->item_name}",
                'detail' => $d->type === 'monetary' ? 'Money' : 'Items',
                'status' => $d->status,
                // Separate pieces so the activity list can lay them out (name / amount / method)
                'actor' => $donorName($d),
                'amount' => $d->type === 'monetary' ? (float) $d->amount : null,
                'items' => $d->type === 'item' ? "{$d->item_quantity}× {$d->item_name}" : null,
                'method' => $d->type === 'monetary'
                    ? ($d->paymentMethod?->name ?? 'Money')
                    : ($d->delivery_method === 'pickup' ? 'Pick-up' : ($d->delivery_method === 'dropoff' ? 'Drop-off' : 'Items')),
                'order' => 3,
            ]);
        }
        foreach ($promotions as $p) {
            $events->push([
                'type' => 'promotion',
                'at' => $p->starts_at->toIso8601String(),
                'title' => "Promoted ({$p->typeLabel()}) for {$p->duration_days} days",
                'detail' => "\"{$p->message}\" · {$p->notified_count} follower(s) notified",
                'status' => $p->isRunning() ? 'running' : 'ended',
                'order' => 2,
            ]);
        }
        foreach ($updates as $u) {
            $events->push([
                'type' => 'update',
                'at' => $u->created_at->toIso8601String(),
                'title' => "Posted an update: {$u->title}",
                'detail' => null,
                'status' => null,
                'order' => 1,
            ]);
        }
        $events->push([
            'type' => 'created',
            'at' => Carbon::parse($campaign->created_at)->toIso8601String(),
            'title' => 'Campaign started',
            'detail' => null,
            'status' => null,
            'order' => 0,
        ]);

        // Newest first; for the same moment, show the later step first (created last).
        // day_key / day_label / time are in the users' local time, for grouping by date.
        $events = $events
            ->sort(fn($a, $b) => [$b['at'], $b['order']] <=> [$a['at'], $a['order']])
            ->values()
            ->map(function ($e) use ($local) {
                $t = $local($e['at']);
                return collect($e)->except('order')->all() + [
                    'day_key' => $t->format('Y-m-d'),
                    'day_label' => $t->format('l, F j, Y'),
                    'time' => $t->format('g:i A'),
                ];
            });

        return response()->json([
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'status' => $campaign->status,
                'type' => $campaign->type,
                'goal_amount' => (float) $campaign->goal_amount,
                'current_amount' => (float) $campaign->current_amount,
                'created_at' => $campaign->created_at,
            ],
            'group' => $group,
            'buckets' => $buckets,
            // start_key / end_key: the first and last chart bucket each promotion covers,
            // worked out here so the chart doesn't depend on the browser's time zone
            'promotions' => $promotions->map(fn($p) => $this->formatPromotion($p) + [
                'start_key' => $keyOf($local($p->starts_at)),
                'end_key' => $keyOf($local($p->ends_at)->subSecond()),
            ])->values(),
            'events' => $events,
        ]);
    }

    private function formatPromotion(CampaignPromotion $p): array
    {
        return [
            'id' => $p->id,
            'type' => $p->type ?? 'urgent',
            'type_label' => $p->typeLabel(),
            'message' => $p->message,
            'duration_days' => $p->duration_days,
            'starts_at' => $p->starts_at->toIso8601String(),
            'ends_at' => $p->ends_at->toIso8601String(),
            'notified_count' => $p->notified_count,
            'running' => $p->isRunning(),
        ];
    }
}
