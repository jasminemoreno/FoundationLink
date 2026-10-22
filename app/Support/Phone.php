<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Philippine mobile numbers, stored as +639XXXXXXXXX.
 * Shared by sign-up and the donor / foundation profile forms.
 */
class Phone
{
    /** Format every stored number must match. */
    public const PATTERN = '/^\+639\d{9}$/';

    /**
     * Normalize 09XXXXXXXXX or +639XXXXXXXXX (spaces/dashes/parens stripped)
     * to +639XXXXXXXXX. Returns null if the input is empty or doesn't match.
     */
    public static function normalize(?string $input): ?string
    {
        if (!$input) {
            return null;
        }

        $cleaned = preg_replace('/[\s\-()]/', '', $input);

        if (preg_match('/^09\d{9}$/', $cleaned)) {
            return '+63' . substr($cleaned, 1);
        }

        if (preg_match(self::PATTERN, $cleaned)) {
            return $cleaned;
        }

        return null;
    }

    /**
     * For profile updates: normalize the submitted phone in place. An entered
     * but invalid number is left as typed so validation rejects it (instead of
     * it becoming null and silently clearing the saved number).
     */
    public static function normalizeRequest(Request $request): void
    {
        if ($request->filled('phone')) {
            $request->merge([
                'phone' => self::normalize($request->input('phone')) ?? $request->input('phone'),
            ]);
        }
    }

    /** Profile rule: valid format and unique among accounts of the same role. */
    public static function profileRule(User $user): array
    {
        return [
            'nullable',
            'regex:' . self::PATTERN,
            Rule::unique('users', 'phone')
                ->where(fn($q) => $q->where('role', $user->role))
                ->ignore($user->id),
        ];
    }

    public const MESSAGES = [
        'phone.regex' => 'Enter a valid PH phone number (09XXXXXXXXX or +639XXXXXXXXX).',
        'phone.unique' => 'This phone number is already registered to another account.',
    ];
}
