<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Address shown to suspended users who need to ask for reactivation. */
    private const SUPPORT_EMAIL = 'jmoreno4@ssct.edu.ph';

    /** Name of the temporary token used during foundation sign-up. */
    public const REGISTRATION_TOKEN_NAME = 'foundation_registration';

    /** Wrong OTP guesses allowed before the code is thrown away. */
    private const MAX_OTP_ATTEMPTS = 5;

    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);

        // Refuse suspended / inactive accounts with a clear reason. This runs only
        // after the password is confirmed, so the message never reveals which
        // emails are registered to someone who does not know the password.
        $account = User::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->first();

        if (
            $account
            && Hash::check($credentials['password'], $account->password)
            && in_array($account->status, ['suspended', 'inactive'], true)
        ) {
            $isSuspended = $account->status === 'suspended';

            if ($isSuspended) {
                $message = 'Your account has been suspended.';

                if (!empty($account->suspension_reason)) {
                    $message .= ' Reason: ' . rtrim(trim($account->suspension_reason), '.') . '.';
                }

                $message .= ' To ask for reactivation, email ' . self::SUPPORT_EMAIL . '.';
            } else {
                $message = 'Your account is not active. Please contact ' . self::SUPPORT_EMAIL . '.';
            }

            // Structured response: the web login page reads `code`, `reason` and
            // `support_email` to show a popup. `message` and `errors.email` are kept
            // so any screen that only reads the plain message (e.g. mobile) still works.
            return response()->json([
                'message' => $message,
                'errors' => ['email' => [$message]],
                'code' => $isSuspended ? 'account_suspended' : 'account_inactive',
                'reason' => $isSuspended ? $account->suspension_reason : null,
                'support_email' => self::SUPPORT_EMAIL,
            ], 403);
        }

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.']
            ]);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }


    /**
     * Register Donor
     */
    public function registerDonor(Request $request)
    {
        // Normalize phone BEFORE validation so the regex/unique checks
        // run against the final +63 format, not whatever format the user typed.
        $request->merge([
            'phone' => Phone::normalize($request->input('phone')),
        ]);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:12',
                'regex:/^(?=.*\d)(?=.*[^a-zA-Z0-9]).{12,}$/',
                'confirmed',
            ],
            'phone' => [
                'required',
                'regex:/^\+639\d{9}$/',
                Rule::unique('users', 'phone')->where(fn($query) => $query->where('role', 'donor')),
            ],
            'address' => 'nullable|string',
            'gender' => 'nullable|in:male,female,prefer_not_to_say',
            'birthdate' => 'nullable|date|before:today',
        ], [
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Enter a valid PH phone number (09XXXXXXXXX or +639XXXXXXXXX).',
            'phone.unique' => 'This phone number is already registered to another donor account.',
            'password.min' => 'Password must be at least 12 characters.',
            'password.regex' => 'Password must include at least 1 number and 1 special character.',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'donor',
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Donor account created successfully.',
            'user' => $user
        ], 201);
    }

    /**
     * Register Foundation Admin
     */
    public function registerFoundation(Request $request)
    {
        $request->merge([
            'phone' => Phone::normalize($request->input('phone')),
        ]);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => [
                'required',
                'regex:/^\+639\d{9}$/',
                Rule::unique('users', 'phone')->where(fn($query) => $query->where('role', 'foundation_admin')),
            ],
            'password' => [
                'required',
                'min:12',
                'regex:/^(?=.*\d)(?=.*[^a-zA-Z0-9]).{12,}$/',
                'confirmed',
            ],
        ], [
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Enter a valid PH phone number (09XXXXXXXXX or +639XXXXXXXXX).',
            'phone.unique' => 'This phone number is already registered to another foundation account.',
            'password.min' => 'Password must be at least 12 characters.',
            'password.regex' => 'Password must include at least 1 number and 1 special character.',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
            'role' => 'foundation_admin',
            'status' => 'active'
        ]);

        // Short-lived token for the next two sign-up steps (create foundation,
        // upload documents) so those endpoints know who is calling without
        // trusting an id sent from the browser. Deleted once documents are submitted.
        $registrationToken = $user->createToken(
            self::REGISTRATION_TOKEN_NAME,
            ['*'],
            now()->addHours(2)
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Foundation admin registered successfully.',
            'user' => $user,
            'registration_token' => $registrationToken,
        ]);
    }

    /**
     * Current User
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    /**
     * Step 1: Forgot Password - generate & email a 6-digit OTP
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        // Same answer whether or not the email is registered, so this form
        // can't be used to find out who has an account.
        $genericResponse = response()->json([
            'success' => true,
            'message' => 'If an account exists for that email, a verification code has been sent.'
        ]);

        if (!$user) {
            return $genericResponse;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        $user->notify(
            new class ($otp) extends Notification {
            public function __construct(public string $otp)
            {}

            public function via($notifiable)
            {
                return ['mail'];
            }

            public function toMail($notifiable)
            {
                return (new MailMessage)
                    ->subject('Your FoundationLink Password Reset Code')
                    ->greeting('Hello!')
                    ->line('You requested to reset your password.')
                    ->line('Your one-time verification code is:')
                    ->line(new \Illuminate\Support\HtmlString(
                        '<h2 style="letter-spacing: 4px;">' . $this->otp . '</h2>'
                    ))
                    ->line('This code will expire in 10 minutes.')
                    ->line('If you did not request this, no further action is required.');
            }
            }
        );

        return $genericResponse;
    }

    /**
     * Step 2: Verify OTP - returns a short-lived reset token on success
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No verification code found. Please request a new one.'
            ], 422);
        }

        // Compare against an explicit deadline: in Carbon 3, now()->diffInMinutes($past)
        // is negative, so a "> 10" check on it never fires.
        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json([
                'success' => false,
                'message' => 'This code has expired. Please request a new one.'
            ], 422);
        }

        if (!Hash::check($request->otp, $record->token)) {
            // Count wrong guesses per email so the 6-digit code can't be brute-forced
            // from many IPs; after too many misses the code is thrown away.
            $attemptsKey = 'otp_attempts:' . strtolower($request->email);
            // add() first: the database cache store's increment() returns false for a missing key.
            Cache::add($attemptsKey, 0, now()->addMinutes(10));
            $attempts = (int) Cache::increment($attemptsKey);

            if ($attempts >= self::MAX_OTP_ATTEMPTS) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();
                Cache::forget($attemptsKey);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many incorrect attempts. Please request a new code.'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code.'
            ], 422);
        }

        Cache::forget('otp_attempts:' . strtolower($request->email));

        // OTP confirmed — issue a short-lived reset token for the final step
        $resetToken = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Code verified.',
            'reset_token' => $resetToken
        ]);
    }

    /**
     * Step 3: Reset Password - using the reset token issued by verifyOtp
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required',
            // Same rules as sign-up
            'password' => [
                'required',
                'confirmed',
                'min:12',
                'regex:/^(?=.*\d)(?=.*[^a-zA-Z0-9]).{12,}$/',
            ],
        ], [
            'password.min' => 'Password must be at least 12 characters.',
            'password.regex' => 'Password must include at least 1 number and 1 special character.',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        $valid = $record
            && Carbon::parse($record->created_at)->addMinutes(15)->isFuture()
            && Hash::check($request->reset_token, $record->token);

        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'This session has expired or is invalid. Please start over.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your password has been reset successfully.'
        ]);
    }
}