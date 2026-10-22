<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['donor', 'foundation_admin'])
            ->latest()
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->first_name . ' ' . $u->last_name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'status' => $u->status ?? 'active',
                    'suspension_reason' => $u->suspension_reason,
                    'phone' => $u->phone,
                    'joined' => $u->created_at->format('M d, Y'),
                    'created_at' => $u->created_at,
                    'initials' => strtoupper(substr($u->first_name, 0, 1) . substr($u->last_name, 0, 1)),
                    'color' => $this->avatarColor($u->id),
                    // 🖼️ profile photo — hardcoded base URL to match the pattern
                    // used elsewhere in the frontend (getImage() helpers)
                    'profile_photo' => $u->profile_photo ? '/storage/' . $u->profile_photo : null,
                ];
            });

        return response()->json($users);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,suspended',
            // A reason is required whenever the account is being suspended.
            'suspension_reason' => 'required_if:status,suspended|nullable|string|min:5|max:500',
        ], [
            'suspension_reason.required_if' => 'Please enter a reason for the suspension.',
            'suspension_reason.min' => 'The reason must be at least 5 characters.',
        ]);

        $user = User::findOrFail($id);

        // Superadmin accounts (including your own) cannot be suspended.
        if ($user->role === 'superadmin') {
            return response()->json([
                'message' => 'Superadmin accounts cannot be suspended.'
            ], 403);
        }

        // Assigned directly (not mass-assigned), so this works even if
        // 'suspension_reason' is not listed in the User model's $fillable.
        $user->status = $validated['status'];
        $user->suspension_reason = $validated['status'] === 'suspended'
            ? trim($validated['suspension_reason'])
            : null;
        $user->save();

        // End any open sessions right away so a suspended user is signed out.
        if ($validated['status'] !== 'active') {
            $user->tokens()->delete();
        }

        return response()->json(['message' => 'Status updated', 'user' => $user]);
    }

    private function avatarColor($id)
    {
        $colors = ['#3b82f6', '#059669', '#ca8a04', '#7c3aed', '#dc2626', '#0891b2', '#db2777'];
        return $colors[$id % count($colors)];
    }
}