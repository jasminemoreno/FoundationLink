<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
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
        // Optional ?foundation_id= and ?category_id= (the foundation's category)
        $request->validate([
            'foundation_id' => 'nullable|integer|exists:foundations,id',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);
        $foundationId = $request->integer('foundation_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $filtered = $foundationId || $categoryId;

        // Campaigns of the chosen foundation / category (deleted ones too, their donations still count)
        $campaignIds = $filtered
            ? Campaign::withTrashed()
                ->when($foundationId, fn($q) => $q->where('foundation_id', $foundationId))
                ->when($categoryId, fn($q) => $q->whereHas('foundation', fn($f) => $f->where('category_id', $categoryId)))
                ->pluck('id')
            : null;
        $scope = fn($q) => $q->when($filtered, fn($q) => $q->whereIn('donations.campaign_id', $campaignIds));

        $donationDate = ReportRange::donationDate();
        $tz = config('app.display_timezone');

        $received = fn(string $type) => $scope($range->apply(
            Donation::where('donations.type', $type)->where('donations.status', 'received'),
            $donationDate
        ));

        /* ── Totals for the chosen dates and filters ── */
        $raised = $received('monetary')->sum('amount');
        $avgDonation = $received('monetary')->avg('amount') ?? 0;
        $itemsReceived = $received('item')->count();

        // Donor accounts aren't tied to a foundation, so with a filter this counts who gave instead
        $donorCard = $filtered
            ? [
                'label' => 'Donors',
                'value' => $scope($range->apply(Donation::where('donations.status', 'received'), $donationDate))
                    ->distinct()->count('donor_id'),
                'change' => 'people whose donation was received',
            ]
            : [
                'label' => 'New Donors',
                'value' => $range->apply(User::where('role', 'donor'), 'created_at')->count(),
                'change' => 'donor accounts created',
            ];

        /* ── KPIs ── */
        $kpis = [
            [
                'label' => 'Money Raised',
                'value' => '₱' . number_format($raised, 0),
                'change' => 'from money donations received',
                'up' => true,
                'bg' => '#f0fdf4',
                'col' => '#059669',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
            ],
            [
                'label' => 'Average Donation',
                'value' => '₱' . number_format($avgDonation, 0),
                'change' => 'per money donation',
                'up' => true,
                'bg' => '#eff6ff',
                'col' => '#3b82f6',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 21V9"/></svg>',
            ],
            [
                'label' => $donorCard['label'],
                'value' => number_format($donorCard['value']),
                'change' => $donorCard['change'],
                'up' => $donorCard['value'] > 0,
                'bg' => '#fefce8',
                'col' => '#ca8a04',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            ],
            [
                'label' => 'Items Received',
                'value' => number_format($itemsReceived),
                'change' => 'item donations received',
                'up' => true,
                'bg' => '#fef2f2',
                'col' => '#dc2626',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
            ],
        ];

        /* ── Money per month: the chosen months, or the last 6 ── */
        $months = $range->months(6);
        $spansYears = reset($months)->year !== end($months)->year;
        $monthly = array_map(function ($month) use ($range, $scope, $donationDate, $spansYears, $tz) {
            $amount = $scope($range->month($month)->apply(
                Donation::where('donations.type', 'monetary')->where('donations.status', 'received'),
                $donationDate
            ))->sum('amount');

            return [
                'month' => $month->format($spansYears ? 'M y' : 'M'),
                'current' => $month->format('Y-m') === now($tz)->format('Y-m'),
                'amount' => round($amount / 1000, 1), // in thousands, for the bar height
                'raised' => (float) $amount,          // pesos, written above the bar
            ];
        }, $months);

        /* ── Donations by foundation category ── */
        $categoryTotals = $scope($range->apply(
            DB::table('donations')
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->join('foundations', 'campaigns.foundation_id', '=', 'foundations.id')
                ->join('categories', 'foundations.category_id', '=', 'categories.id')
                ->where('donations.type', 'monetary')
                ->where('donations.status', 'received'),
            $donationDate
        ))
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
        $topDonors = $scope($range->apply(
            DB::table('donations')
                ->join('users', 'donations.donor_id', '=', 'users.id')
                ->where('donations.type', 'monetary')
                ->where('donations.status', 'received'),
            $donationDate
        ))
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
            ->when($foundationId, fn($q) => $q->where('foundations.id', $foundationId))
            ->when($categoryId, fn($q) => $q->where('foundations.category_id', $categoryId))
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
            'filters' => [
                'foundation' => $foundationId ? Foundation::find($foundationId)?->name : null,
                'category' => $categoryId ? Category::find($categoryId)?->name : null,
            ],
            // Choices for the filter dropdowns: approved (or suspended) foundations, and all categories
            'options' => [
                'foundations' => Foundation::whereIn('status', ['verified', 'suspended'])
                    ->orderBy('name')->get(['id', 'name', 'category_id']),
                'categories' => Category::orderBy('name')->get(['id', 'name']),
            ],
            'kpis' => $kpis,
            'monthly' => $monthly,
            'categories' => $categories,
            'top_donors' => $topDonors,
            'top_foundations' => $topFoundations,
        ]);
    }
}
