<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $from = $this->periodStart($period);

        /* ── Totals ── */
        $totalRaised = Donation::where('type', 'monetary')->where('status', 'received')->sum('amount');
        $totalDonations = Donation::count();
        $totalDonors = User::where('role', 'donor')->count();
        $completedCampaigns = Campaign::where('status', 'completed')->count();

        /* ── Period-scoped totals for % change ── */
        $periodRaised = Donation::where('type', 'monetary')->where('status', 'received')->where('created_at', '>=', $from)->sum('amount');
        $periodDonors = User::where('role', 'donor')->where('created_at', '>=', $from)->count();
        $periodDonations = Donation::where('created_at', '>=', $from)->count();
        $avgDonation = $periodDonations > 0
            ? Donation::where('type', 'monetary')->where('created_at', '>=', $from)->avg('amount')
            : 0;

        /* ── KPIs ── */
        $kpis = [
            [
                'label' => 'Total Raised',
                'value' => '₱' . number_format($totalRaised, 0),
                'change' => '₱' . number_format($periodRaised, 0) . ' this period',
                'up' => $periodRaised >= 0,
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
                'value' => number_format($periodDonors),
                'change' => 'registered this period',
                'up' => $periodDonors > 0,
                'bg' => '#fefce8',
                'col' => '#ca8a04',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            ],
            [
                'label' => 'Campaigns Completed',
                'value' => number_format($completedCampaigns),
                'change' => 'total completed',
                'up' => true,
                'bg' => '#fef2f2',
                'col' => '#dc2626',
                'icon' => '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>',
            ],
        ];

        /* ── Monthly donations (last 6 months) ── */
        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthKey = $month->format('Y-m');
            $amount = Donation::where('type', 'monetary')
                ->where('status', 'received')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$monthKey])
                ->sum('amount');
            $monthly[] = [
                'month' => $month->format('M'),
                'amount' => round($amount / 1000, 1), // in thousands
            ];
        }

        /* ── Donations by foundation category ── */
        $categoryTotals = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->join('foundations', 'campaigns.foundation_id', '=', 'foundations.id')
            ->join('categories', 'foundations.category_id', '=', 'categories.id')
            ->where('donations.type', 'monetary')
            ->where('donations.status', 'received')
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
        $topDonors = DB::table('donations')
            ->join('users', 'donations.donor_id', '=', 'users.id')
            ->where('donations.type', 'monetary')
            ->where('donations.status', 'received')
            ->when($from, fn($q) => $q->where('donations.created_at', '>=', $from))
            ->select(
                'users.id',
                DB::raw("CONCAT(users.first_name, ' ', users.last_name) as name"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(donations.amount) as total')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->values()
            ->map(function ($d, $i) use ($avatarColors) {
                $parts = explode(' ', trim($d->name));
                return [
                    'name' => $d->name,
                    'initials' => strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1)),
                    'color' => $avatarColors[$i % count($avatarColors)],
                    'count' => $d->count,
                    'amount' => '₱' . number_format($d->total, 0),
                ];
            })
            ->toArray();

        /* ── Top foundations ── */
        $topFoundations = Foundation::withCount('campaigns')
            ->withSum([
                'donations as total_raised' => function ($q) {
                    $q->where('donations.type', 'monetary')->where('donations.status', 'received');
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
            'kpis' => $kpis,
            'monthly' => $monthly,
            'categories' => $categories,
            'top_donors' => $topDonors,
            'top_foundations' => $topFoundations,
        ]);
    }

    /* ── helpers ── */
    private function periodStart(string $period): ?Carbon
    {
        return match ($period) {
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }
}