<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Category;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        /* ── STATS ── */
        $totalFoundations = Foundation::count();

        $pendingFoundations = Foundation::whereIn('status', [
            'pending_verification',
            'under_review',
            'incomplete'
        ])->count();

        $totalDonors = User::where('role', 'donor')->count();

        $activeCampaigns = Campaign::where('status', 'active')->count();
        $totalCampaigns = Campaign::count();

        $totalDonations = Donation::where('status', 'received')->sum('amount');

        /* ── TOP CAMPAIGNS (real, by amount raised — monetary/both only) ── */
        $barColors = ['#059669', '#4f46e5', '#0891b2', '#D4AF37', '#dc2626'];

        $topCampaigns = Campaign::where('status', 'active')
            ->whereIn('type', ['monetary', 'both'])
            ->orderByDesc('current_amount')
            ->take(5)
            ->get()
            ->values()
            ->map(function ($c, $i) use ($barColors) {
                $pct = $c->goal_amount > 0
                    ? min((int) round(($c->current_amount / $c->goal_amount) * 100), 100)
                    : 0;

                return [
                    'name' => $c->title,
                    'amount' => '₱' . number_format($c->current_amount),
                    'pct' => $pct,
                    'col' => $barColors[$i] ?? '#64748b',
                ];
            });

        /* ── TOP ITEM DRIVES (real, by total items donated — item/both only) ── */
        $topItemDrives = Campaign::where('status', 'active')
            ->whereIn('type', ['item', 'both'])
            ->withSum([
                'donations as items_donated' => function ($q) {
                    $q->where('status', 'received')
                        ->where('type', 'item');
                }
            ], 'item_quantity')
            ->withCount([
                'donations as donors_count' => function ($q) {
                    $q->select(DB::raw('COUNT(DISTINCT donor_id)'))
                        ->where('status', 'received')
                        ->where('type', 'item');
                }
            ])
            ->orderByDesc('items_donated')
            ->take(5)
            ->get()
            ->values()
            ->map(function ($c, $i) use ($barColors) {
                return [
                    'name' => $c->title,
                    'count' => (int) ($c->items_donated ?? 0),
                    'unit' => 'items',
                    'donors' => (int) $c->donors_count,
                    'col' => $barColors[$i] ?? '#64748b',
                ];
            });

        /* ── CATEGORY BREAKDOWN (real, via addSelect subquery since
               Category has no defined campaigns() relation) ── */
        $categories = Category::query()
            ->addSelect([
                'campaigns_count' => Campaign::selectRaw('COUNT(*)')
                    ->whereColumn('campaigns.category_id', 'categories.id'),
            ])
            ->get();

        $maxCatCount = $categories->max('campaigns_count') ?: 1;

        $categoryData = $categories
            ->sortByDesc('campaigns_count')
            ->values()
            ->map(function ($cat) use ($maxCatCount) {
                return [
                    'name' => $cat->name,
                    'count' => (int) $cat->campaigns_count,
                    'pct' => (int) round(($cat->campaigns_count / $maxCatCount) * 100),
                    'color' => $cat->color ?? '#0F2D52',
                ];
            });

        /* ── RECENT ACTIVITY (real, merged from 3 tables — no
               activity_logs table exists, so this is built live) ── */
        $recentFoundations = Foundation::latest()->take(5)->get()->map(function ($f) {
            return [
                'msg' => "{$f->name} registered as a new foundation",
                'time' => $f->created_at,
                'type' => 'info',
            ];
        });

        $recentDonations = Donation::with(['donor', 'campaign'])
            ->where('status', 'received')
            ->latest('donated_at')
            ->take(5)
            ->get()
            ->map(function ($d) {
                $donorName = $d->donor
                    ? trim($d->donor->first_name . ' ' . $d->donor->last_name)
                    : 'A donor';

                $campaignTitle = $d->campaign->title ?? 'a campaign';

                $desc = $d->type === 'monetary'
                    ? 'donated ₱' . number_format($d->amount) . " to {$campaignTitle}"
                    : "donated {$d->item_name} to {$campaignTitle}";

                return [
                    'msg' => "{$donorName} {$desc}",
                    'time' => $d->donated_at ?? $d->created_at,
                    'type' => 'success',
                ];
            });

        $recentCampaigns = Campaign::with('foundation')->latest()->take(5)->get()->map(function ($c) {
            $foundationName = $c->foundation->name ?? 'A foundation';
            return [
                'msg' => "{$foundationName} launched a new campaign: {$c->title}",
                'time' => $c->created_at,
                'type' => 'warning',
            ];
        });

        $activities = $recentFoundations
            ->concat($recentDonations)
            ->concat($recentCampaigns)
            ->sortByDesc(fn($a) => Carbon::parse($a['time']))
            ->take(8)
            ->values()
            ->map(function ($a) {
                return [
                    'msg' => $a['msg'],
                    'time' => Carbon::parse($a['time'])->diffForHumans(),
                    'type' => $a['type'],
                ];
            });

        return response()->json([
            'stats' => [
                [
                    'label' => 'Total Foundations',
                    'value' => $totalFoundations,
                    'sub' => $pendingFoundations . ' pending approval',
                    'trend' => 0,
                    'progress' => $totalFoundations > 0 ? 100 : 0,
                    'color' => 'blue',
                ],
                [
                    'label' => 'Active Campaigns',
                    'value' => $activeCampaigns,
                    'sub' => $totalCampaigns . ' total campaigns',
                    'trend' => 0,
                    'progress' => $totalCampaigns > 0
                        ? (int) round(($activeCampaigns / $totalCampaigns) * 100)
                        : 0,
                    'color' => 'gold',
                ],
                [
                    'label' => 'Total Donors',
                    'value' => $totalDonors,
                    'sub' => 'Registered donors',
                    'trend' => 0,
                    'progress' => $totalDonors > 0 ? 100 : 0,
                    'color' => 'teal',
                ],
                [
                    'label' => 'Total Donations',
                    'value' => '₱' . number_format($totalDonations),
                    'sub' => 'All-time received',
                    'trend' => 0,
                    'progress' => $totalDonations > 0 ? 100 : 0,
                    'color' => 'green',
                ],
            ],
            'pendingApprovals' => Foundation::whereIn('status', [
                'pending_verification',
                'under_review',
                'incomplete'
            ])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($f) {
                    return [
                        'name' => $f->name,
                        'type' => 'Foundation',
                        'date' => $f->created_at->diffForHumans(),
                        'initials' => strtoupper(substr($f->name, 0, 2)),
                        'color' => '#4f46e5'
                    ];
                }),
            'topCampaigns' => $topCampaigns,
            'topItemDrives' => $topItemDrives,
            'categories' => $categoryData,
            'activities' => $activities,
            'message' => 'Dashboard loaded successfully'
        ]);
    }

    public function approveFoundation($id)
    {
        $foundation = Foundation::with('user')->findOrFail($id);

        $foundation->update([
            'status' => 'verified',
            'verified_at' => now(),
            'rejection_reason' => null
        ]);

        // send email
        Mail::raw(
            "Congratulations! Your foundation has been approved and you may now access FoundationLink.",
            function ($message) use ($foundation) {
                $message->to($foundation->user->email)
                    ->subject('Foundation Approved');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Foundation approved.'
        ]);
    }

    public function rejectFoundation(Request $request, $id)
    {
        $foundation = Foundation::with('user')->findOrFail($id);

        $foundation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        Mail::raw(
            "Your foundation verification was rejected.\n\nReason: " . $request->reason,
            function ($message) use ($foundation) {
                $message->to($foundation->user->email)
                    ->subject('Foundation Verification Rejected');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'Foundation rejected.'
        ]);
    }

    public function campaigns()
    {
        $campaigns = Campaign::with(['foundation:id,name', 'photos'])
            ->addSelect([
                'donors_count' => Donation::selectRaw('COUNT(DISTINCT donor_id)')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', 'received'),
            ])
            ->latest()
            ->get();

        return response()->json($campaigns->map(function ($c) {
            return [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'status' => $c->status,
                'type' => $c->type,
                'raised' => (float) $c->current_amount,
                'goal' => (float) $c->goal_amount,
                'donors_count' => (int) $c->donors_count,
                'start_date' => $c->start_date ? $c->start_date->format('M d, Y') : null,
                'end_date' => $c->end_date ? $c->end_date->format('M d, Y') : null,
                'pause_reason' => $c->pause_reason,
                'accepted_delivery_methods' => $c->accepted_delivery_methods,
                'cover_photo_url' => $c->cover_photo ? asset('storage/' . $c->cover_photo) : null,
                'photos' => $c->photos->map(fn($p) => [
                    'id' => $p->id,
                    'url' => asset('storage/' . $p->photo_path),
                    'caption' => $p->caption,
                ]),
                'foundation' => $c->foundation ? ['id' => $c->foundation->id, 'name' => $c->foundation->name] : null,
            ];
        }));
    }
    public function pendingApprovalsCount()
    {
        $count = Foundation::whereIn('status', [
            'pending_verification',
            'under_review',
            'incomplete'
        ])->count();

        return response()->json(['count' => $count]);
    }

    /* ══════════════════════════════
       NOTIFICATIONS (superadmin)
       Mirrors the pattern already used in FoundationController
       and DonorController — same Notification model, scoped to
       whichever superadmin is currently logged in.
    ══════════════════════════════ */
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->limit(20)->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function notificationsCount()
    {
        return response()->json([
            'count' => auth()->user()->notifications()->whereNull('read_at')->count(),
        ]);
    }

    public function markNotificationsSeen()
    {
        auth()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function markOneRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);
        return response()->json(['message' => 'Marked as read']);
    }

    public function deleteNotification($id)
    {
        auth()->user()->notifications()->findOrFail($id)->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    public function clearAllNotifications()
    {
        auth()->user()->notifications()->delete();
        return response()->json(['message' => 'All notifications cleared']);
    }
}