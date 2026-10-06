<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Foundation;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\FoundationVerificationSubmitted;
use App\Mail\FoundationEmailChangeVerification;
use App\Mail\FoundationEmailChangeNotice;

class FoundationController extends Controller
{
    /**
     * CREATE FOUNDATION
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'nullable|exists:categories,id',

            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',

            'logo' => 'nullable|string',
            'cover_photo' => 'nullable|string',
        ]);

        $foundation = Foundation::create([
            'user_id' => $validated['user_id'],
            'category_id' => $validated['category_id'] ?? null,

            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,

            'street' => $validated['street'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'city_municipality' => $validated['city_municipality'] ?? null,
            'province' => $validated['province'] ?? null,

            'logo' => $validated['logo'] ?? null,
            'cover_photo' => $validated['cover_photo'] ?? null,

            'status' => 'pending_verification',
        ]);

        // Notify all superadmins that a new foundation registered
        Notification::notifySuperadmins(
            'foundation_registered',
            'New foundation registration',
            "{$foundation->name} has registered and is awaiting verification.",
            $foundation
        );

        return response()->json([
            'message' => 'Foundation created successfully',
            'foundation' => $foundation
        ], 201);
    }

    /**
     * LIST ALL FOUNDATIONS (ADMIN VIEW)
     */
    public function index()
    {
        $foundations = Foundation::with(['user', 'documents', 'category'])
            ->withCount('campaigns')
            ->withCount('followers')
            ->addSelect([
                'total_raised' => \App\Models\Donation::selectRaw('COALESCE(SUM(amount), 0)')
                    ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
                    ->whereColumn('campaigns.foundation_id', 'foundations.id')
                    ->where('donations.status', 'received'),

                'donors_count' => \App\Models\Donation::selectRaw('COUNT(DISTINCT donor_id)')
                    ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
                    ->whereColumn('campaigns.foundation_id', 'foundations.id')
                    ->where('donations.status', 'received'),
            ])
            ->latest()
            ->get();

        return response()->json($foundations->map(function ($f) {

            return [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,

                'street' => $f->street,
                'barangay' => $f->barangay,
                'city_municipality' => $f->city_municipality,
                'province' => $f->province,

                'full_address' => collect([
                    $f->street,
                    $f->barangay,
                    $f->city_municipality,
                    $f->province
                ])->filter()->implode(', '),

                'category' => $f->category ? ['id' => $f->category->id, 'name' => $f->category->name] : null,
                'category_id' => $f->category_id,

                // 🖼️ logo — hardcoded base URL to match the pattern used
                // elsewhere in the frontend (getImage() helpers)
                'logo' => $f->logo ? '/storage/' . $f->logo : null,

                'status' => $f->status,
                'created_at' => $f->created_at,

                'user' => $f->user,

                'campaigns_count' => $f->campaigns_count,
                'followers_count' => $f->followers_count,
                'total_raised' => (float) $f->total_raised,
                'donors_count' => (int) $f->donors_count,

                'identity_documents' => $f->documents
                    ->where('type', 'identity')
                    ->map(fn($d) => [
                        'id' => $d->id,
                        'file_path' => $d->file_path,
                    ])->values(),

                'legitimacy_documents' => $f->documents
                    ->where('type', 'legitimacy')
                    ->map(fn($d) => [
                        'id' => $d->id,
                        'file_path' => $d->file_path,
                    ])->values(),
            ];
        }));
    }

    /**
     * SHOW SINGLE FOUNDATION
     */
    public function show($id)
    {
        return Foundation::with(['user', 'documents', 'category'])
            ->findOrFail($id);
    }

    /**
     * FOUNDATION ADMIN DASHBOARD
     */
    public function dashboard()
    {
        $foundation = Foundation::with('category')
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'foundation' => $foundation,
            'can_donate' => $foundation && $foundation->status === 'verified',
            'followers_count' => $foundation ? $foundation->followers()->count() : 0,
            'likes_count' => $foundation ? $foundation->likers()->count() : 0,
        ]);
    }

    public function suspend($id)
    {
        $foundation = Foundation::findOrFail($id);
        $foundation->update(['status' => 'suspended']);
        return response()->json(['message' => 'Foundation suspended']);
    }

    public function unsuspend($id)
    {
        $foundation = Foundation::findOrFail($id);
        $foundation->update(['status' => 'verified']);
        return response()->json(['message' => 'Foundation unsuspended']);
    }

    /* ══════════════════════════════
       GET SETTINGS
    ══════════════════════════════ */
    public function settings()
    {
        $foundation = Foundation::with('category')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return response()->json($foundation);
    }

    /* ══════════════════════════════
       UPDATE SETTINGS
    ══════════════════════════════ */
    public function updateSettings(Request $request)
    {
        $foundation = Foundation::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:4096',
            'cover_photo' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('logo')) {
            if ($foundation->logo)
                \Storage::disk('public')->delete($foundation->logo);
            $validated['logo'] = $request->file('logo')->store('foundations/logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($foundation->cover_photo)
                \Storage::disk('public')->delete($foundation->cover_photo);
            $validated['cover_photo'] = $request->file('cover_photo')->store('foundations/covers', 'public');
        }

        $foundation->update($validated);

        return response()->json([
            'message' => 'Settings updated successfully',
            'foundation' => $foundation->load('category'),
        ]);
    }

    /* ══════════════════════════════
       RESUBMIT AFTER REJECTION
    ══════════════════════════════ */
    public function resubmit(Request $request)
    {
        $foundation = Foundation::where('user_id', auth()->id())->firstOrFail();

        if ($foundation->status !== 'rejected') {
            return response()->json([
                'message' => 'Only rejected foundations can be resubmitted.'
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'street' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',

            'admin_id_type' => 'nullable|string',
            'doc_type' => 'nullable|string',
            'admin_files' => 'nullable|array|min:1|max:2',
            'admin_files.*' => 'image|max:5120',
            'doc_files' => 'nullable|array|min:1|max:2',
            'doc_files.*' => 'image|max:5120',
        ]);

        $foundation->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'street' => $validated['street'] ?? null,
            'barangay' => $validated['barangay'] ?? null,
            'city_municipality' => $validated['city_municipality'] ?? null,
            'province' => $validated['province'] ?? null,
            'status' => 'under_review',
            'rejection_reason' => null,
        ]);

        if ($request->hasFile('admin_files')) {
            $foundation->documents()->where('type', 'identity')->delete();

            foreach ($request->file('admin_files') as $file) {
                $path = $file->store('foundation/admin_ids', 'public');

                $foundation->documents()->create([
                    'type' => 'identity',
                    'document_type' => $validated['admin_id_type'] ?? null,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'status' => 'pending',
                ]);
            }
        }

        if ($request->hasFile('doc_files')) {
            $foundation->documents()->where('type', 'legitimacy')->delete();

            foreach ($request->file('doc_files') as $file) {
                $path = $file->store('foundation/documents', 'public');

                $foundation->documents()->create([
                    'type' => 'legitimacy',
                    'document_type' => $validated['doc_type'] ?? null,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientOriginalExtension(),
                    'status' => 'pending',
                ]);
            }
        }

        $superAdmin = User::where('role', 'superadmin')->first();

        if ($superAdmin && $superAdmin->email) {
            Mail::to($superAdmin->email)
                ->send(new FoundationVerificationSubmitted($foundation));
        }

        Notification::notifySuperadmins(
            'foundation_resubmitted',
            'Foundation resubmitted for review',
            "{$foundation->name} has resubmitted their verification for review.",
            $foundation
        );

        return response()->json([
            'message' => 'Foundation resubmitted for review.',
            'foundation' => $foundation->load('documents'),
        ]);
    }

    /* ══════════════════════════════
       PROFILE
    ══════════════════════════════ */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /* ══════════════════════════════
       REQUEST EMAIL CHANGE
       Stores the new address as pending_email and emails a
       confirmation link to it. The live `email` column is not
       touched until the user presses "Yes, confirm" on the
       page that link opens.
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
       (from inside the logged-in profile page)
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
       EMAIL CHANGE — PUBLIC (TOKEN) ENDPOINTS
       These three are public (no auth) — the token itself is the
       proof, since the user may open the link from a different
       browser/session than the one that requested the change.
       Shared by foundation admins and donors (lookup is by token).
    ══════════════════════════════ */

    /** Find the user that owns a still-pending email change for this token. */
    private function findPendingEmailChange(string $token): ?User
    {
        return User::where('email_change_token', hash('sha256', $token))
            ->whereNotNull('pending_email')
            ->first();
    }

    /**
     * PREVIEW — read-only. Lets the Confirm/Cancel page show which
     * address the change is for. Changes nothing.
     */
    public function previewEmailChange($token)
    {
        $user = $this->findPendingEmailChange($token);

        if (!$user) {
            return response()->json([
                'message' => 'This verification link is invalid or has already been used.',
            ], 404);
        }

        if ($user->email_change_expires_at && now()->greaterThan($user->email_change_expires_at)) {
            return response()->json([
                'message' => 'This verification link has expired. Please request a new email change.',
            ], 410);
        }

        return response()->json([
            'new_email' => $user->pending_email,
        ]);
    }

    /**
     * "YES, CONFIRM" — swaps pending_email into email.
     */
    public function confirmEmailChange(Request $request, $token)
    {
        $user = $this->findPendingEmailChange($token);

        if (!$user) {
            return response()->json([
                'message' => 'This verification link is invalid or has already been used.',
            ], 404);
        }

        if ($user->email_change_expires_at && now()->greaterThan($user->email_change_expires_at)) {
            return response()->json([
                'message' => 'This verification link has expired. Please request a new email change.',
            ], 410);
        }

        $oldEmail = $user->email;
        $newEmail = $user->pending_email;

        // Someone else may have registered this address since the request was made.
        $emailTaken = User::where('email', $newEmail)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($emailTaken) {
            return response()->json([
                'message' => 'This email is already in use by another account.',
            ], 422);
        }

        $user->update([
            'email' => $newEmail,
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_expires_at' => null,
        ]);

        // The change is already saved at this point, so a mail failure
        // must not turn the response into an error.
        if ($oldEmail) {
            try {
                Mail::to($oldEmail)->send(new FoundationEmailChangeNotice($user, $newEmail));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => "Your email has been updated to {$newEmail}.",
            'new_email' => $newEmail,
        ]);
    }

    /**
     * "NO, CANCEL" — clears the pending change; the account email stays as it is.
     */
    public function declineEmailChange($token)
    {
        $user = $this->findPendingEmailChange($token);

        if (!$user) {
            return response()->json([
                'message' => 'This verification link is invalid or has already been used.',
            ], 404);
        }

        $user->update([
            'pending_email' => null,
            'email_change_token' => null,
            'email_change_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'The email change was cancelled. Your account email has not been changed.',
        ]);
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

            'current_password' => 'nullable|required_with:new_password|string',
            'new_password' => [
                'nullable',
                'confirmed',
                'min:12',
                'regex:/^(?=.*[0-9])(?=.*[^A-Za-z0-9]).+$/',
            ],
        ], [
            'new_password.min' => 'New password must be at least 12 characters.',
            'new_password.regex' => 'New password must include at least one number and one special character.',
        ]);

        if (!empty($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect.',
                    'errors' => ['current_password' => ['Current password is incorrect.']]
                ], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        foreach (['first_name', 'last_name', 'phone', 'address', 'gender', 'birthdate'] as $field) {
            if (array_key_exists($field, $validated)) {
                $user->{$field} = $validated[$field];
            }
        }

        $user->save();

        return response()->json(['message' => 'Profile updated', 'user' => $user->fresh()]);
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
       NOTIFICATIONS
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