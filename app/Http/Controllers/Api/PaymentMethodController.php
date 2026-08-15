<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    /* ══════════════════════════════
       LIST ALL (super admin)
    ══════════════════════════════ */
    public function index()
    {
        $methods = PaymentMethod::latest()->get();
        return response()->json($methods);
    }

    /* ══════════════════════════════
       LIST ACTIVE (for foundation + donor)
    ══════════════════════════════ */
    public function active()
    {
        $methods = PaymentMethod::where('is_active', true)->latest()->get();
        return response()->json($methods);
    }

    /* ══════════════════════════════
       CREATE
    ══════════════════════════════ */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:payment_methods,name',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $method = PaymentMethod::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon ?? '💳',
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json(['message' => 'Payment method created.', 'payment_method' => $method], 201);
    }

    /* ══════════════════════════════
       UPDATE
    ══════════════════════════════ */
    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:100|unique:payment_methods,name,' . $id,
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($request->has('name')) {
            $method->slug = Str::slug($request->name);
        }

        $method->update($request->only(['name', 'icon', 'description', 'is_active']));
        if ($request->has('name')) {
            $method->update(['slug' => Str::slug($request->name)]);
        }

        return response()->json(['message' => 'Payment method updated.', 'payment_method' => $method]);
    }

    /* ══════════════════════════════
       TOGGLE ACTIVE
    ══════════════════════════════ */
    public function toggle($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->update(['is_active' => !$method->is_active]);

        return response()->json([
            'message' => 'Payment method ' . ($method->is_active ? 'activated' : 'deactivated') . '.',
            'payment_method' => $method,
        ]);
    }

    /* ══════════════════════════════
       DELETE
    ══════════════════════════════ */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();
        return response()->json(['message' => 'Payment method deleted.']);
    }
}