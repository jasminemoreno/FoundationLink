<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use App\Support\ReportRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminReportController extends Controller
{
    public function index(Request $request)
    {
        // Optional ?from=YYYY-MM-DD&to=YYYY-MM-DD; none = all time
        $range = ReportRange::fromRequest($request);
        $donationDate = ReportRange::donationDate();
        $tz = config('app.display_timezone');

        $receivedMoney = fn() => $range->apply(
            Donation::where('type', 'monetary')->where('status', 'received'),
            $donationDate
        );

        /* ── Totals for the chosen dates ── */
        $raised = $receivedMoney()->sum('amount');
        $avgDonation = $receivedMoney()->avg('amount') ?? 0;
        $newDonors = $range->apply(User::where('role', 'donor'), 'created_at')->count();
        $itemsReceived = $range->apply(
            Donation::where('type', 'item')->where('status', 'received'),
            $donationDate
        )->count();

        /* ── KPIs ── */
        $kpis = [
            [
                'label' => 'Total Raised',
                'value' => '₱' . number_format($raised, 0),
                'change' => $range->label(),
                'up' => true,
                'bg' => '#f0fdf4',
                'col' => '#059669',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            ],
            [
                'label' => 'Avg Donation Size',
                'value' => '₱' . number_format($avgDonation, 0),
                'change' => 'monetary donations',
                'up' => true,
                'bg' => '#eff6ff',
                'col' => '#3b82f6',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 21V9"/></svg>',
            ],
            [
                'label' => 'New Donors',
                'value' => number_format($newDonors),
                'change' => $range->isAllTime() ? 'registered in total' : 'registered in this period',
                'up' => $newDonors > 0,
                'bg' => '#fefce8',
                'col' => '#ca8a04',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            ],
            [
                'label' => 'Items Received',
                'value' => number_format($itemsReceived),
                'change' => 'item donations',
                'up' => true,
                'bg' => '#fef2f2',
                'col' => '#dc2626',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            ],
        ];

        /* ── Money per month: the chosen months, or the last 6 ── */
        $months = $range->months(6);
        $spansYears = reset($months)->year !== end($months)->year;
        $monthly = array_map(function ($month) use ($range, $donationDate, $spansYears, $tz) {
            $amount = $range->month($month)->apply(
                Donation::where('type', 'monetary')->where('status', 'received'),
                $donationDate
            )->sum('amount');

            return [
                'month' => $month->format($spansYears ? 'M y' : 'M'),
                'current' => $month->format('Y-m') === now($tz)->format('Y-m'),
                'amount' => round($amount / 1000, 1), // in thousands
            ];
        }, $months);

        /* ── Donations by foundation category ── */
        $categoryTotals = $range->apply(
            DB::table('donations')
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->join('foundations', 'campaigns.foundation_id', '=', 'foundations.id')
                ->join('categories', 'foundations.category_id', '=', 'categories.id')
                ->where('donations.type', 'monetary')
                ->where('donations.status', 'received'),
            $donationDate
        )
            ->select('categories.name as cat', DB::raw('SUM(donations.amount) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $categoryTotals->sum('total') ?: 1;
        $colors = ['#0F2D52', '#D4AF37', '#059669', '#3b82f6', '#dc2626', '#8b5cf6'];

        $categories = $categoryTotals->values()->map(function ($row, $i) use ($grandTotal, $colors) {
            return [
                'cat' => $row->cat,
                'pct' => round(($row->total / $grandTotal) * 100),
                'color' => $colors[$i % count($colors)],
            ];
        })->toArray();

        /* ── Top donors ── */
        $avatarColors = ['#0F2D52', '#059669', '#D4AF37', '#3b82f6', '#8b5cf6', '#dc2626'];
        $topDonors = $range->apply(
            DB::table('donations')
                ->join('users', 'donations.donor_id', '=', 'users.id')
                ->where('donations.type', 'monetary')
                ->where('donations.status', 'received'),
            $donationDate
        )
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(donations.amount) as total')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($d, $i) use ($avatarColors) {
                // Name joined in PHP rather than SQL CONCAT, which isn't portable across databases
                $name = trim($d->first_name . ' ' . $d->last_name);
                return [
                    'name' => $name,
                    'initials' => strtoupper(substr($d->first_name ?? '', 0, 1) . substr($d->last_name ?? '', 0, 1)),
                    'color' => $avatarColors[$i % count($avatarColors)],
                    'count' => $d->count,
                    'amount' => '₱' . number_format($d->total, 0),
                ];
            })
            ->toArray();

        /* ── Top foundations ── */
        $topFoundations = Foundation::withCount('campaigns')
            ->withSum([
                'donations as total_raised' => function ($q) use ($range, $donationDate) {
                    $range->apply($q->where('donations.type', 'monetary')->where('donations.status', 'received'), $donationDate);
                }
            ], 'amount')
            ->orderByDesc('total_raised')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($f, $i) use ($avatarColors) {
                $words = explode(' ', $f->name);
                return [
                    'name' => $f->name,
                    'initials' => strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1)),
                    'color' => $avatarColors[$i % count($avatarColors)],
                    'campaigns' => $f->campaigns_count,
                    'raised' => '₱' . number_format($f->total_raised ?? 0, 0),
                ];
            })
            ->toArray();

        return response()->json([
            'period' => $range->label(),
            'kpis' => $kpis,
            'monthly' => $monthly,
            'categories' => $categories,
            'top_donors' => $topDonors,
            'top_foundations' => $topFoundations,
        ]);
    }
}
