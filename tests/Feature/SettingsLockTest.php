<?php

namespace Tests\Feature;

use App\Models\Foundation;
use App\Models\FoundationPaymentAccount;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Settings (foundation info, branding, payment accounts) stay locked until the
 * admin has finished sign-up, i.e. added the foundation and uploaded documents.
 */
class SettingsLockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PaymentMethod $gcash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'foundation_admin']);
        $this->gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        Sanctum::actingAs($this->admin);
    }

    private function makeFoundation(string $status): Foundation
    {
        return Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => $status]);
    }

    private function saveSettings()
    {
        return $this->postJson('/api/foundation/settings', ['name' => 'Renamed']);
    }

    private function savePaymentAccount()
    {
        return $this->postJson('/api/foundation/payment-accounts', ['accounts' => [[
            'payment_method_id' => $this->gcash->id, 'account_name' => 'Helping Hands', 'account_number' => '09170000000',
        ]]]);
    }

    public function test_settings_are_locked_when_the_foundation_was_never_added(): void
    {
        $this->saveSettings()->assertStatus(403);
        $this->savePaymentAccount()->assertStatus(403);
    }

    public function test_settings_are_locked_when_documents_were_never_uploaded(): void
    {
        $foundation = $this->makeFoundation('pending_verification');

        $this->saveSettings()->assertStatus(403);
        $this->savePaymentAccount()->assertStatus(403);
        $this->deleteJson("/api/foundation/payment-accounts/{$this->gcash->id}")->assertStatus(403);

        $this->assertSame('Helping Hands', $foundation->fresh()->name);
        $this->assertSame(0, FoundationPaymentAccount::count());
    }

    public function test_settings_unlock_once_documents_are_sent(): void
    {
        foreach (['under_review', 'verified'] as $status) {
            Foundation::where('user_id', $this->admin->id)->forceDelete();
            $foundation = $this->makeFoundation($status);

            $this->saveSettings()->assertOk();
            $this->assertSame('Renamed', $foundation->fresh()->name, "Settings should save when {$status}");
            $this->savePaymentAccount()->assertOk();
        }
    }
}
