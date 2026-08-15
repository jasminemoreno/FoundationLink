<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\FoundationPaymentAccount;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class FoundationPaymentAccountController extends Controller
{
    private function getFoundation()
    {
        return Foundation::where('user_id', auth()->id())->firstOrFail();
    }

    /* ══════════════════════════════
       LIST — all active payment methods
       with foundation's account if set
    ══════════════════════════════ */
    public function index()
    {
        $foundation = $this->getFoundation();

        // Get all active payment methods
        $methods = PaymentMethod::where('is_active', true)->get();

        // Get foundation's existing accounts
        $accounts = FoundationPaymentAccount::where('foundation_id', $foundation->id)
            ->with('paymentMethod')
            ->get()
            ->keyBy('payment_method_id');

        $result = $methods->map(function ($method) use ($accounts) {
            $account = $accounts->get($method->id);
            return [
                'payment_method_id' => $method->id,
                'name' => $method->name,
                'slug' => $method->slug,
                'icon' => $method->icon,
                'description' => $method->description,
                'account_name' => $account?->account_name ?? '',
                'account_number' => $account?->account_number ?? '',
                'is_configured' => $account !== null,
            ];
        });

        return response()->json($result);
    }

    /* ══════════════════════════════
       SAVE (upsert) — foundation sets
       their account per payment method
    ══════════════════════════════ */
    public function save(Request $request)
    {
        $request->validate([
            'accounts' => 'required|array',
            'accounts.*.payment_method_id' => 'required|exists:payment_methods,id',
            'accounts.*.account_name' => 'required|string|max:255',
            'accounts.*.account_number' => 'required|string|max:255',
        ]);

        $foundation = $this->getFoundation();

        foreach ($request->accounts as $item) {
            FoundationPaymentAccount::updateOrCreate(
                [
                    'foundation_id' => $foundation->id,
                    'payment_method_id' => $item['payment_method_id'],
                ],
                [
                    'account_name' => $item['account_name'],
                    'account_number' => $item['account_number'],
                ]
            );
        }

        return response()->json(['message' => 'Payment accounts saved successfully.']);
    }

    /* ══════════════════════════════
       DELETE one account
    ══════════════════════════════ */
    public function destroy($paymentMethodId)
    {
        $foundation = $this->getFoundation();

        FoundationPaymentAccount::where('foundation_id', $foundation->id)
            ->where('payment_method_id', $paymentMethodId)
            ->delete();

        return response()->json(['message' => 'Account removed.']);
    }
}