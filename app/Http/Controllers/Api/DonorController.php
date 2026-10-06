<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Notification;
use App\Models\Foundation;
use App\Models\FoundationPaymentAccount;
use App\Models\User;
use App\Mail\FoundationEmailChangeVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DonorController extends Controller
{
    /* ══════════════════════════════
       DASHBOARD
    ══════════════════════════════ */
    public function dashboard()
    {
        $donor = auth()->user();
        $donations = Donation::with('campaign')
            ->where('donor_id', $donor->id)
            ->latest()->get();

        $featured = Campaign::with(['foundation', 'foundation.category', 'category'])
            ->where('status', 'active')
            ->latest()->take(6)->get();

        return response()->json([
            'donor' => $donor,
            'total_donated' => $donations->where('type', 'monetary')->sum('amount'),
            'total_items' => $donations->where('type', 'item')->count(),
            'campaigns_supported' => $donations->pluck('campaign_id')->unique()->count(),
            'recent_donations' => $donations->take(5)->map(fn($d) => $this->formatDonation($d)),
            'featured_campaigns' => $featured->map(fn($c) => $this->formatCampaign($c)),
        ]);
    }

    /* ══════════════════════════════
       BROWSE CAMPAIGNS
    ══════════════════════════════ */
    public function campaigns(Request $request)
    {
        $donor = auth()->user();
        $tab = $request->get('tab', 'new');

        $query = Campaign::with(['foundation', 'foundation.category', 'category']);

        if ($tab === 'followed') {
            $followedIds = DB::table('foundation_followers')
                ->where('donor_id', $donor->id)
                ->where('type', 'follow')
                ->pluck('foundation_id');
            $query->whereIn('foundation_id', $followedIds);
        } elseif ($tab === 'completed') {
            $query->where('status', 'completed');
        } elseif ($tab === 'paused') {
            $query->where('status', 'paused');
        } else {
            // 'new' (default) = active campaigns
            $query->where('status', 'active');
        }

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->category) {
            // Filters by the CAMPAIGN's own category, not the foundation's
            $query->where('category_id', $request->category);
        }

        $campaigns = $query->latest()->get();

        return response()->json($campaigns->map(fn($c) => $this->formatCampaign($c)));
    }

    /* ══════════════════════════════
       CAMPAIGN DETAIL
    ══════════════════════════════ */
    public function campaignDetail($id)
    {
        $campaign = Campaign::with(['foundation', 'foundation.category', 'category', 'photos'])
            ->where('status', 'active')
            ->findOrFail($id);

        return response()->json($this->formatCampaign($campaign, true));
    }

    /* ══════════════════════════════
       DONATE
    ══════════════════════════════ */
    public function donate(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'type' => 'required|in:monetary,item,both',
            'amount' => 'nullable|numeric|min:1',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'proof_photo' => 'nullable|image|max:4096',
            'item_name' => 'nullable|string|max:255',
            'item_quantity' => 'nullable|integer|min:1',
            'item_description' => 'nullable|string',
            'item_photo' => 'nullable|image|max:4096',
            'delivery_method' => 'nullable|in:pickup,dropoff',
            'delivery_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        $campaign = Campaign::with('foundation')->findOrFail($request->campaign_id);

        if ($campaign->status !== 'active') {
            return response()->json(['message' => 'This campaign is not accepting donations.'], 422);
        }

        if ($campaign->type !== 'both' && $campaign->type !== $request->type) {
            return response()->json([
                'message' => 'This campaign does not accept ' . $request->type . ' donations.'
            ], 422);
        }

        if (in_array($request->type, ['monetary', 'both']) && !$request->amount) {
            return response()->json(['message' => 'Please enter a monetary amount.'], 422);
        }
        if (in_array($request->type, ['monetary', 'both']) && !$request->payment_method_id) {
            return response()->json(['message' => 'Please select a payment method.'], 422);
        }
        if (in_array($request->type, ['item', 'both']) && !$request->item_name) {
            return response()->json(['message' => 'Please enter an item name.'], 422);
        }
        if (in_array($request->type, ['item', 'both']) && !$request->item_quantity) {
            return response()->json(['message' => 'Please enter a quantity.'], 422);
        }
        if (in_array($request->type, ['item', 'both']) && !$request->delivery_method) {
            return response()->json(['message' => 'Please choose a delivery method.'], 422);
        }
        if ($request->delivery_method === 'pickup' && !$request->delivery_address) {
            return response()->json(['message' => 'Please enter your pick-up address.'], 422);
        }

        $donor = auth()->user();
        $now = now();
        $created = [];

        $proofPhotoPath = null;
        if ($request->hasFile('proof_photo')) {
            $proofPhotoPath = $request->file('proof_photo')->store('donations/proofs', 'public');
        }

        $itemPhotoPath = null;
        if ($request->hasFile('item_photo')) {
            $itemPhotoPath = $request->file('item_photo')->store('donations/items', 'public');
        }

        if (in_array($request->type, ['monetary', 'both'])) {
            $donation = Donation::create([
                'campaign_id' => $campaign->id,
                'donor_id' => $donor->id,
                'type' => 'monetary',
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'proof_photo' => $proofPhotoPath,
                'notes' => $request->notes ?? null,
                'status' => 'pending',
                'donated_at' => $now,
            ]);
            $campaign->increment('current_amount', $request->amount);
            $created[] = $donation;

            $this->notifyFoundationOfDonation($campaign, $donor, '₱' . number_format($request->amount, 2));
        }

        if (in_array($request->type, ['item', 'both'])) {
            $donation = Donation::create([
                'campaign_id' => $campaign->id,
                'donor_id' => $donor->id,
                'type' => 'item',
                'item_name' => $request->item_name,
                'item_quantity' => $request->item_quantity,
                'item_description' => $request->item_description ?? null,
                'item_photo' => $itemPhotoPath,
                'delivery_method' => $request->delivery_method,
                'delivery_address' => $request->delivery_address ?? null,
                'notes' => $request->notes ?? null,
                'status' => 'pending',
                'donated_at' => $now,
            ]);
            $created[] = $donation;

            $this->notifyFoundationOfDonation(
                $campaign,
                $donor,
                $request->item_quantity . 'x ' . $request->item_name
            );
        }

        return response()->json([
            'message' => 'Donation submitted successfully!',
            'donations' => $created,
        ], 201);
    }

    /* ══════════════════════════════
       CANCEL AN ITEM DONATION (donor-initiated)
       Rules: the donor must own it, it must be an item donation,
       and it must still be pending. Monetary donations are not
       cancellable by donors — payment happens outside the system,
       so only the foundation admin can mark those cancelled.
       Item donations never touch campaigns.current_amount, so no
       campaign total needs adjusting here.
    ══════════════════════════════ */
    public function cancelItemDonation($id)
    {
        $donor = auth()->user();

        $donation = Donation::with('campaign.foundation')
            ->where('id', $id)
            ->where('donor_id', $donor->id)
            ->firstOrFail();

        if ($donation->type !== 'item') {
            return response()->json([
                'message' => 'Only item donations can be cancelled.',
            ], 422);
        }

        if ($donation->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending donations can be cancelled.',
            ], 422);
        }

        $donation->update(['status' => 'cancelled']);

        $this->notifyFoundationOfCancellation($donation->campaign, $donor, $donation);

        return response()->json([
            'message' => 'Your item donation has been cancelled.',
            'donation' => $this->formatDonation($donation),
        ]);
    }

    /* ══════════════════════════════
       NOTIFY FOUNDATION ADMIN OF A NEW DONATION
    ══════════════════════════════ */
    private function notifyFoundationOfDonation(Campaign $campaign, $donor, string $summary): void
    {
        $foundation = $campaign->foundation;

        if (!$foundation || !$foundation->user_id) {
            return;
        }

        $donorName = trim(($donor->first_name ?? '') . ' ' . ($donor->last_name ?? '')) ?: 'A donor';

        Notification::create([
            'user_id' => $foundation->user_id,
            'type' => 'donation_received',
            'title' => 'New donation received',
            'message' => "{$donorName} donated {$summary} to \"{$campaign->title}\".",
            'notifiable_id' => $campaign->id,
            'notifiable_type' => 'campaign',
        ]);
    }

    /* ══════════════════════════════
       NOTIFY FOUNDATION ADMIN THAT A DONOR CANCELLED AN ITEM DONATION
    ══════════════════════════════ */
    private function notifyFoundationOfCancellation(Campaign $campaign, $donor, Donation $donation): void
    {
        $foundation = $campaign->foundation;

        if (!$foundation || !$foundation->user_id) {
            return;
        }

        $donorName = trim(($donor->first_name ?? '') . ' ' . ($donor->last_name ?? '')) ?: 'A donor';

        Notification::create([
            'user_id' => $foundation->user_id,
            'type' => 'donation_cancelled',
            'title' => 'Item donation cancelled',
            'message' => "{$donorName} cancelled their item donation of {$donation->item_quantity}x {$donation->item_name} to \"{$campaign->title}\".",
            'notifiable_id' => $campaign->id,
            'notifiable_type' => 'campaign',
        ]);
    }

    /* ══════════════════════════════
       MY DONATIONS
    ══════════════════════════════ */
    public function myDonations()
    {
        $donations = Donation::with('campaign.foundation')
            ->where('donor_id', auth()->id())
            ->latest()
            ->get();

        return response()->json($donations->map(fn($d) => $this->formatDonation($d)));
    }

    /* ══════════════════════════════
       NOTIFICATIONS
    ══════════════════════════════ */
    public function notifications()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->take(50)
            ->get();

        return response()->json($notifications->map(fn($n) => [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'date' => $n->created_at,
            'read' => $n->read_at !== null,
            'link' => $this->resolveNotificationLink($n),
        ]));
    }

    public function notificationsCount()
    {
        $count = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markNotificationsSeen()
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifications marked as read']);
    }

    /* ══════════════════════════════
       MARK ONE NOTIFICATION AS READ (used on click)
    ══════════════════════════════ */
    public function markOneRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Notification marked as read']);
    }

    /* ══════════════════════════════
       PROFILE
    ══════════════════════════════ */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female,prefer_not_to_say',
            'birthdate' => 'nullable|date',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

    /* ══════════════════════════════
       REQUEST EMAIL CHANGE
       Stores the new address as pending_email and emails a
       confirmation link to it. The live `email` column is not
       touched until the link is clicked. Confirmation itself is
       handled by the shared public route
       POST /foundation/profile/email/confirm/{token}
       (FoundationController::confirmEmailChange), which looks the
       user up by token only, so it works for donors too.
    ══════════════════════════════ */
    public function requestEmailChange(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'new_email' => 'required|string|email|max:255',
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        if (strtolower($validated['new_email']) === strtolower($user->email)) {
            return response()->json([
                'message' => 'This is already your current email address.',
                'errors' => ['new_email' => ['This is already your current email address.']],
            ], 422);
        }

        $emailTaken = User::where('email', $validated['new_email'])
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            return response()->json([
                'message' => 'This email is already in use by another account.',
                'errors' => ['new_email' => ['This email is already in use by another account.']],
            ], 422);
        }

        $token = Str::random(64);

        $user->update([
            'pending_email' => $validated['new_email'],
            'email_change_token' => hash('sha256', $token),
            'email_change_expires_at' => now()->addHours(24),
        ]);

        Mail::to($validated['new_email'])->send(new FoundationEmailChangeVerification($user, $token));

        return response()->json([
            'message' => "A verification link has been sent to {$validated['new_email']}. Click it to confirm the change.",
            'pending_email' => $validated['new_email'],
        ]);
    }

    /* ══════════════════════════════
       CANCEL PENDING EMAIL CHANGE
    ══════════════════════════════ */
    public function cancelEmailChange()
    {
        auth()->user()->update([
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_expires_at' => null,
        ]);

        return response()->json(['message' => 'Pending email change cancelled.']);
    }

    /* ══════════════════════════════
       UPLOAD PROFILE PHOTO
    ══════════════════════════════ */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|max:4096',
        ]);

        $user = auth()->user();

        if ($user->profile_photo) {
            \Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('profile_photo')->store('profile_photos', 'public');

        $user->update(['profile_photo' => $path]);

        return response()->json([
            'message' => 'Profile photo updated',
            'user' => $user,
        ]);
    }

    /* ══════════════════════════════
       ALL FOUNDATIONS
    ══════════════════════════════ */
    public function foundations()
    {
        $donor = auth()->user();

        $foundations = Foundation::with(['category'])
            ->where('status', 'verified')
            ->withCount([
                'campaigns as total_campaigns',
                'campaigns as active_campaigns' => fn($q) => $q->where('status', 'active'),
                'campaigns as completed_campaigns' => fn($q) => $q->where('status', 'completed'),
                'followers',
                'likers',
            ])
            ->latest()
            ->get();

        $followedIds = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('type', 'follow')
            ->pluck('foundation_id')
            ->toArray();

        $likedIds = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('type', 'like')
            ->pluck('foundation_id')
            ->toArray();

        return response()->json($foundations->map(function ($f) use ($followedIds, $likedIds) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,
                'logo' => $f->logo,
                'cover_photo' => $f->cover_photo,
                'category' => $f->category?->name,
                'city_municipality' => $f->city_municipality,
                'province' => $f->province,
                'status' => $f->status,

                'total_campaigns' => $f->total_campaigns ?? 0,
                'active_campaigns' => $f->active_campaigns ?? 0,
                'completed_campaigns' => $f->completed_campaigns ?? 0,

                'followers_count' => $f->followers_count ?? 0,
                'likes_count' => $f->likers_count ?? 0,

                'is_followed' => in_array($f->id, $followedIds),
                'is_liked' => in_array($f->id, $likedIds),

                'initials' => collect(explode(' ', $f->name))
                    ->map(fn($w) => strtoupper($w[0] ?? ''))
                    ->take(2)->join(''),
            ];
        }));
    }

    /* ══════════════════════════════
        FOUNDATION DETAIL
     ══════════════════════════════ */
    public function foundationDetail($id)
    {
        $donor = auth()->user();

        $f = Foundation::with(['category'])
            ->where('status', 'verified')
            ->findOrFail($id);

        $campaigns = Campaign::with('photos')
            ->where('foundation_id', $f->id)
            ->latest()
            ->get();

        $isFollowed = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $id)
            ->where('type', 'follow')
            ->exists();

        $isLiked = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $id)
            ->where('type', 'like')
            ->exists();

        $updatesByCampaign = CampaignUpdate::whereIn('campaign_id', $campaigns->pluck('id'))
            ->with('reactions')
            ->latest()
            ->get()
            ->groupBy('campaign_id');

        return response()->json([
            'id' => $f->id,
            'name' => $f->name,
            'description' => $f->description,
            'logo' => $f->logo,
            'cover_photo' => $f->cover_photo,
            'category' => $f->category?->name,
            'street' => $f->street,
            'barangay' => $f->barangay,
            'city_municipality' => $f->city_municipality,
            'province' => $f->province,
            'full_address' => collect([$f->street, $f->barangay, $f->city_municipality, $f->province])
                ->filter()->implode(', '),
            'status' => $f->status,
            'member_since' => $f->created_at,

            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'completed_campaigns' => $campaigns->where('status', 'completed')->count(),
            'total_raised' => $campaigns->sum('current_amount'),

            'followers_count' => $f->followers()->count(),
            'likes_count' => $f->likers()->count(),
            'is_followed' => $isFollowed,
            'is_liked' => $isLiked,

            'initials' => collect(explode(' ', $f->name))
                ->map(fn($w) => strtoupper($w[0] ?? ''))
                ->take(2)->join(''),

            'campaigns' => $campaigns->map(function ($c) use ($updatesByCampaign, $donor) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'description' => $c->description,
                    'cover_photo' => $c->cover_photo,
                    'type' => $c->type,
                    'status' => $c->status,
                    'goal_amount' => $c->goal_amount,
                    'current_amount' => $c->current_amount,
                    'start_date' => $c->start_date,
                    'end_date' => $c->end_date,
                    'pause_reason' => $c->pause_reason,
                    'accepted_delivery_methods' => $c->accepted_delivery_methods ?? [],
                    'accepted_payment_methods' => $this->enrichPaymentMethods($c),
                    'percent' => $c->goal_amount > 0
                        ? min(round(($c->current_amount / $c->goal_amount) * 100), 100)
                        : 0,
                    'photos' => $c->photos->map(fn($p) => [
                        'id' => $p->id,
                        'photo_path' => $p->photo_path,
                        'caption' => $p->caption,
                    ]),
                    'updates' => ($updatesByCampaign->get($c->id) ?? collect())->map(fn($u) => [
                        'id' => $u->id,
                        'campaign_id' => $u->campaign_id,
                        'title' => $u->title,
                        'content' => $u->content,
                        'photos' => $u->photos ?? [],
                        'posted_at' => $u->created_at->diffForHumans(),
                        'total_reactions' => $u->reactions->count(),
                        'my_reaction' => $u->reactions->contains('donor_id', $donor->id),
                    ])->values(),
                ];
            }),
        ]);
    }
    /* ══════════════════════════════
       HELPERS
    ══════════════════════════════ */
    private function resolveNotificationLink(Notification $n): ?string
    {
        return match ($n->type) {
            'new_campaign' => '/donor/campaigns?tab=new&highlight=' . $n->notifiable_id,
            'campaign_update' => '/donor/updates?highlight=' . $n->notifiable_id,
            'donation_received', 'donation_cancelled' => '/donor/donations?highlight=' . $n->notifiable_id,
            default => null,
        };
    }

    private function formatCampaign($c, $detail = false): array
    {
        $data = [
            'id' => $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'cover_photo' => $c->cover_photo,
            'type' => $c->type,
            'category' => $c->category?->name,
            'goal_amount' => $c->goal_amount,
            'current_amount' => $c->current_amount,
            'start_date' => $c->start_date,
            'end_date' => $c->end_date,
            'status' => $c->status,
            'pause_reason' => $c->pause_reason,
            'accepted_delivery_methods' => $c->accepted_delivery_methods ?? [],
            'accepted_payment_methods' => $this->enrichPaymentMethods($c),
            'percent' => $c->goal_amount > 0
                ? min(round(($c->current_amount / $c->goal_amount) * 100), 100)
                : 0,
            'foundation' => [
                'id' => $c->foundation->id,
                'name' => $c->foundation->name,
                'category' => $c->foundation->category?->name,
                'logo' => $c->foundation->logo,
                'street' => $c->foundation->street,
                'barangay' => $c->foundation->barangay,
                'city_municipality' => $c->foundation->city_municipality,
                'province' => $c->foundation->province,
            ],
        ];

        if ($detail) {
            $data['photos'] = $c->photos->map(fn($p) => [
                'id' => $p->id,
                'photo_path' => $p->photo_path,
                'caption' => $p->caption,
            ]);
        }

        return $data;
    }

    private function enrichPaymentMethods(Campaign $c): array
    {
        if (empty($c->accepted_payment_methods))
            return [];

        $accounts = FoundationPaymentAccount::with('paymentMethod')
            ->where('foundation_id', $c->foundation_id)
            ->whereIn('payment_method_id', $c->accepted_payment_methods)
            ->get();

        return $accounts->map(fn($a) => [
            'payment_method_id' => $a->payment_method_id,
            'name' => $a->paymentMethod->name,
            'icon' => $a->paymentMethod->icon,
            'account_name' => $a->account_name,
            'account_number' => $a->account_number,
        ])->toArray();
    }

    private function formatDonation($d): array
    {
        return [
            'id' => $d->id,
            'type' => $d->type,
            'status' => $d->status,
            'amount' => $d->amount,
            'item_name' => $d->item_name,
            'item_quantity' => $d->item_quantity,
            'item_description' => $d->item_description,
            'delivery_method' => $d->delivery_method,
            'delivery_address' => $d->delivery_address,
            'notes' => $d->notes,
            'proof_photo' => $d->proof_photo,
            'item_photo' => $d->item_photo,
            'donated_at' => $d->donated_at ?? $d->created_at,
            'campaign' => [
                'id' => $d->campaign->id,
                'title' => $d->campaign->title,
                'type' => $d->campaign->type,
                'foundation' => $d->campaign->foundation?->name,
            ],
        ];
    }
    /* ══════════════════════════════
       DELETE ONE NOTIFICATION
    ══════════════════════════════ */
    public function deleteNotification($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    /* ══════════════════════════════
       CLEAR ALL NOTIFICATIONS
    ══════════════════════════════ */
    public function clearAllNotifications()
    {
        Notification::where('user_id', auth()->id())->delete();

        return response()->json(['message' => 'All notifications cleared']);
    }
    /* ══════════════════════════════
       ALL CATEGORIES (for filter dropdowns)
    ══════════════════════════════ */
    public function categories()
    {
        return response()->json(
            \App\Models\Category::orderBy('name')->get(['id', 'name'])
        );
    }
}