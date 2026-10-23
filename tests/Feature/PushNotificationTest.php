<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\Notification;
use App\Models\PushToken;
use App\Models\User;
use App\Support\ExpoPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phone push notifications for the donor mobile app: every in-app notification
 * is also sent to the donor's phone(s) through Expo, without ever breaking the
 * action that created it.
 */
class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const EXPO_URL = 'https://exp.host/--/api/v2/push/send';
    private const TOKEN = 'ExponentPushToken[abc123DEF456ghi789]';

    private User $donor;
    private User $admin;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->donor = $this->makeUser('donor');
        $this->admin = $this->makeUser('foundation_admin');
        $foundation = Foundation::create(['user_id' => $this->admin->id, 'name' => 'Helping Hands', 'status' => 'verified']);
        $this->campaign = Campaign::create([
            'foundation_id' => $foundation->id,
            'created_by' => $this->admin->id,
            'title' => 'School Supplies Drive',
            'description' => '',
            'type' => 'item',
            'goal_amount' => 0,
            'current_amount' => 0,
            'status' => 'active',
        ]);
    }

    private function makeUser(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'first_name' => 'Test',
            'last_name' => "User{$n}",
            'email' => "push{$n}@example.com",
            'password' => 'Secret-pass-123!',
            'role' => $role,
            'phone' => '+63919000' . str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]);
    }

    private function pendingDonation(): Donation
    {
        return Donation::create([
            'campaign_id' => $this->campaign->id,
            'donor_id' => $this->donor->id,
            'type' => 'item',
            'item_name' => 'Notebook',
            'item_quantity' => 5,
            'delivery_method' => 'dropoff',
            'status' => 'pending',
            'donated_at' => now(),
        ]);
    }

    private function markReceived(Donation $donation)
    {
        Sanctum::actingAs($this->admin);

        return $this->patchJson("/api/foundation/donations/{$donation->id}/status", ['status' => 'received']);
    }

    private function expoAnswers(array $tickets, int $status = 200): void
    {
        Http::fake([self::EXPO_URL => Http::response(['data' => $tickets], $status)]);
    }

    /* ── Saving the phone's push address ── */

    public function test_the_app_saves_and_removes_the_phones_push_address(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk();
        $this->postJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk(); // saved once, not twice
        $this->assertSame(1, PushToken::where('user_id', $this->donor->id)->count());

        $this->deleteJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk();
        $this->assertSame(0, PushToken::count());
    }

    public function test_only_real_expo_push_addresses_are_accepted(): void
    {
        Sanctum::actingAs($this->donor);

        $this->postJson('/api/donor/push-token', ['token' => 'not-a-push-token'])->assertStatus(422);
        $this->postJson('/api/donor/push-token', [])->assertStatus(422);
        $this->assertSame(0, PushToken::count());
    }

    public function test_a_shared_phone_only_gets_the_pushes_of_whoever_logged_in_last(): void
    {
        $other = $this->makeUser('donor');

        Sanctum::actingAs($this->donor);
        $this->postJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk();
        Sanctum::actingAs($other);
        $this->postJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk();

        $this->assertSame([$other->id], PushToken::pluck('user_id')->all());
    }

    public function test_a_donor_cannot_remove_someone_elses_push_address(): void
    {
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        Sanctum::actingAs($this->makeUser('donor'));

        $this->deleteJson('/api/donor/push-token', ['token' => self::TOKEN])->assertOk();

        $this->assertSame(1, PushToken::count());
    }

    /* ── Sending ── */

    public function test_a_new_notification_is_also_pushed_to_the_donors_phone(): void
    {
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        $this->expoAnswers([['status' => 'ok', 'id' => 'ticket-1']]);
        $donation = $this->pendingDonation();

        $this->markReceived($donation)->assertOk();

        $notification = Notification::where('user_id', $this->donor->id)->sole();
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($notification, $donation) {
            $message = $request->data()[0];

            return $request->url() === self::EXPO_URL
                && $message['to'] === self::TOKEN
                && $message['title'] === 'Donation Received!'
                && $message['body'] === $notification->message
                && $message['data']['notification_id'] === $notification->id
                && $message['data']['link'] === "/donor/donations?highlight={$donation->id}"
                && $message['channelId'] === 'default';
        });
    }

    public function test_a_campaign_update_push_opens_the_foundation_page(): void
    {
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        $this->expoAnswers([['status' => 'ok', 'id' => 'ticket-1']]);
        $update = CampaignUpdate::create(['campaign_id' => $this->campaign->id, 'title' => 'Thanks!', 'content' => 'Done.', 'photos' => []]);

        Notification::create([
            'user_id' => $this->donor->id, 'title' => 'Campaign Update', 'message' => 'Thanks!',
            'type' => 'campaign_update', 'notifiable_id' => $update->id, 'notifiable_type' => CampaignUpdate::class,
        ]);
        app(ExpoPush::class)->flush();

        Http::assertSent(fn(Request $r) => $r->data()[0]['data']['link'] === "/donor/foundations/{$this->campaign->foundation_id}");
    }

    public function test_nothing_is_sent_for_people_without_the_app(): void
    {
        Http::fake();

        $this->markReceived($this->pendingDonation())->assertOk();

        $this->assertSame(1, Notification::where('user_id', $this->donor->id)->count());
        Http::assertNothingSent();
    }

    public function test_if_expo_is_down_the_action_and_the_in_app_notification_still_work(): void
    {
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        // A real refused connection (nothing listens on port 9). Faking one with
        // Http::failedConnection() crashes this machine's PHP 8.2 build, while a
        // real connection error is caught normally.
        config(['services.expo.push_url' => 'http://127.0.0.1:9/--/api/v2/push/send']);
        Log::spy(); // check the warning instead of writing it to the real log file
        $donation = $this->pendingDonation();

        $this->markReceived($donation)->assertOk();

        $this->assertSame('received', $donation->fresh()->status);
        $this->assertSame(1, Notification::where('user_id', $this->donor->id)->count());
        $this->assertSame(1, PushToken::count()); // a network problem is not a reason to forget the phone
        Log::shouldHaveReceived('warning')->withArgs(fn($message) => str_starts_with($message, 'Expo push request failed'));
    }

    public function test_an_expo_error_answer_does_not_break_anything_either(): void
    {
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        Http::fake([self::EXPO_URL => Http::response(['errors' => [['code' => 'INTERNAL_SERVER_ERROR']]], 500)]);
        Log::spy();

        $this->markReceived($this->pendingDonation())->assertOk();

        $this->assertSame(1, Notification::where('user_id', $this->donor->id)->count());
        Log::shouldHaveReceived('warning')->withArgs(fn($message) => $message === 'Expo push request refused');
    }

    public function test_phones_that_uninstalled_the_app_are_forgotten(): void
    {
        $gone = 'ExponentPushToken[uninstalled000000000]';
        PushToken::create(['user_id' => $this->donor->id, 'token' => $gone]);
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        $this->expoAnswers([
            ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']],
            ['status' => 'ok', 'id' => 'ticket-2'],
        ]);

        $this->markReceived($this->pendingDonation())->assertOk();

        $this->assertSame([self::TOKEN], PushToken::pluck('token')->all());
    }

    public function test_many_notifications_go_out_in_groups_of_100(): void
    {
        $donors = collect(range(1, 150))->map(fn() => $this->makeUser('donor'));
        foreach ($donors as $i => $d) {
            PushToken::create(['user_id' => $d->id, 'token' => "ExponentPushToken[bulk{$i}xxxxxxxxxxxx]"]);
        }
        Http::fake([self::EXPO_URL => fn(Request $r) => Http::response(['data' => array_fill(0, count($r->data()), ['status' => 'ok', 'id' => 'x'])])]);

        foreach ($donors as $d) {
            Notification::create(['user_id' => $d->id, 'title' => 'New campaign', 'message' => 'Help us', 'type' => 'new_campaign', 'notifiable_id' => $this->campaign->id, 'notifiable_type' => Campaign::class]);
        }
        app(ExpoPush::class)->flush();

        Http::assertSentCount(2);
        Http::assertSent(fn(Request $r) => count($r->data()) === 100);
        Http::assertSent(fn(Request $r) => count($r->data()) === 50);
    }

    public function test_pushes_can_be_switched_off(): void
    {
        config(['services.expo.push_enabled' => false]);
        PushToken::create(['user_id' => $this->donor->id, 'token' => self::TOKEN]);
        Http::fake();

        $this->markReceived($this->pendingDonation())->assertOk();

        Http::assertNothingSent();
    }
}
