<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;

/**
 * The mobile app registers the phone's Expo push address after login and
 * removes it on logout, so pushes only reach phones where the donor is
 * logged in.
 */
class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string', 'max:191', 'regex:' . PushToken::FORMAT],
        ], [
            'token.regex' => 'This is not a valid Expo push token.',
        ]);

        // One row per phone: if someone else logs in on it, the phone is theirs now
        PushToken::updateOrCreate(
            ['token' => $request->token],
            ['user_id' => auth()->id()]
        );

        return response()->json(['message' => 'Push notifications are on for this phone.']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['token' => 'required|string|max:191']);

        PushToken::where('token', $request->token)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['message' => 'Push notifications are off for this phone.']);
    }
}
