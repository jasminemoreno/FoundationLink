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
        $request->validate([
            'status' => 'required|in:active,inactive,suspended'
        ]);

        $user = User::findOrFail($id);
        $user->update(['status' => $request->status]);

        return response()->json(['message' => 'Status updated', 'user' => $user]);
    }

    private function avatarColor($id)
    {
        $colors = ['#3b82f6', '#059669', '#ca8a04', '#7c3aed', '#dc2626', '#0891b2', '#db2777'];
        return $colors[$id % count($colors)];
    }
}