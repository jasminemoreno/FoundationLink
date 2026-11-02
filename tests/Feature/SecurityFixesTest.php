<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\FoundationDocument;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\PrivateFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Fixes from the security check:
 * - IDs, foundation documents and payment receipts open only through a signed link
 *   that the API gives to the people allowed to see them, and that link expires;
 * - a fake X-Forwarded-For can't dodge the login lock or the reset-email limit,
 *   and many addresses together can't guess one account's password forever;
 * - sign-ups are limited per address;
 * - logins expire after 30 days, and changing a password logs out other devices;
 * - a suspended foundation can't schedule pick-ups.
 */
class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Secret-pass-123!';

    private function makeUser(string $role = 'donor'): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'first_name' => 'Test', 'last_name' => "User{$n}", 'email' => "sec{$n}@example.com",
            'password' => self::PASSWORD, 'role' => $role,
            'phone' => '+63917100' . str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'status' => 'active',
        ]);
    }

    private function makeCampaign(string $status = 'verified'): Campaign
    {
        $admin = $this->makeUser('foundation_admin');
        $foundation = Foundation::create(['user_id' => $admin->id, 'name' => "Foundation {$admin->id}", 'status' => $status]);
        $gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);

        return Campaign::create([
            'foundation_id' => $foundation->id, 'created_by' => $admin->id, 'title' => 'Drive', 'description' => '',
            'type' => 'both', 'goal_amount' => 1000, 'current_amount' => 0, 'status' => 'active',
            'accepted_payment_methods' => [(string) $gcash->id],
            'accepted_delivery_methods' => ['pickup', 'dropoff'],
        ]);
    }

    private function fakeDisks(): void
    {
        Storage::fake('local');
        Storage::fake('public');
    }

    private function donationWithReceipt(User $donor, Campaign $campaign, string $path = 'donations/proofs/r.jpg'): Donation
    {
        Storage::disk('local')->put($path, 'receipt');

        return Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $donor->id, 'type' => 'monetary', 'amount' => 500,
            'proof_photo' => $path, 'status' => 'pending', 'donated_at' => now(),
        ]);
    }

    private function signed(string $path, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('private-file', now()->addMinutes($minutes), ['path' => $path], false);
    }

    private function wrongLogin(string $email, string $remoteAddress, ?string $forwardedFor = null, ?string $password = null)
    {
        $headers = $forwardedFor ? ['X-Forwarded-For' => $forwardedFor] : [];

        return $this->withServerVariables(['REMOTE_ADDR' => $remoteAddress])
            ->postJson('/api/login', ['email' => $email, 'password' => $password ?? 'Wrong-pass-999!'], $headers);
    }

    private function signUp(string $url, string $remoteAddress = '198.51.100.1')
    {
        // incomplete on purpose: a refused sign-up still counts, and this keeps the test fast
        return $this->withServerVariables(['REMOTE_ADDR' => $remoteAddress])->postJson($url, ['first_name' => 'Bot']);
    }

    /* ── Private files: where uploads are saved ── */

    public function test_a_payment_receipt_is_saved_privately_but_an_item_photo_stays_public(): void
    {
        $this->fakeDisks();
        $campaign = $this->makeCampaign();
        Sanctum::actingAs($this->makeUser());

        $this->post('/api/donor/donate', [
            'campaign_id' => $campaign->id, 'type' => 'both', 'amount' => 500,
            'payment_method_id' => PaymentMethod::where('slug', 'gcash')->value('id'),
            'proof_photo' => UploadedFile::fake()->image('receipt.jpg'),
            'item_name' => 'Rice', 'item_quantity' => 1, 'delivery_method' => 'dropoff',
            'item_photo' => UploadedFile::fake()->image('rice.jpg'),
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $money = Donation::where('type', 'monetary')->first();
        Storage::disk('local')->assertExists($money->proof_photo);
        Storage::disk('public')->assertMissing($money->proof_photo);

        $item = Donation::where('type', 'item')->first();
        Storage::disk('public')->assertExists($item->item_photo);
    }

    public function test_verification_documents_are_saved_privately(): void
    {
        $this->fakeDisks();
        $admin = $this->makeUser('foundation_admin');
        $foundation = Foundation::create(['user_id' => $admin->id, 'name' => 'New', 'status' => 'pending_verification']);
        Sanctum::actingAs($admin);

        $this->post('/api/foundation-verification', [
            'admin_id_type' => 'passport', 'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')],
            'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $paths = FoundationDocument::where('foundation_id', $foundation->id)->pluck('file_path');
        $this->assertCount(2, $paths);
        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_resubmitted_documents_are_saved_privately(): void
    {
        $this->fakeDisks();
        $admin = $this->makeUser('foundation_admin');
        $foundation = Foundation::create(['user_id' => $admin->id, 'name' => 'Rejected', 'status' => 'rejected']);
        Sanctum::actingAs($admin);

        $res = $this->post('/api/foundation/resubmit', [
            'name' => 'Rejected', 'admin_id_type' => 'passport', 'doc_type' => 'sec_registration',
            'admin_files' => [UploadedFile::fake()->image('id.jpg')],
            'doc_files' => [UploadedFile::fake()->image('doc.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $paths = FoundationDocument::where('foundation_id', $foundation->id)->pluck('file_path');
        $this->assertCount(2, $paths);
        foreach ($paths as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }

        // the admin's own answer links to them too
        $this->get($res->json('foundation.documents.0.file_url'))->assertOk();
    }

    /* ── Private files: who gets a link, and what the link does ── */

    public function test_the_donor_and_the_foundation_get_a_link_that_opens_the_receipt_without_a_login(): void
    {
        $this->fakeDisks();
        $donor = $this->makeUser();
        $campaign = $this->makeCampaign();
        $donation = $this->donationWithReceipt($donor, $campaign);

        Sanctum::actingAs($donor);
        $fromDonor = $this->getJson("/api/donor/donations/{$donation->id}")->json('proof_photo');
        $fromList = $this->getJson('/api/donor/donations')->json('0.proof_photo');

        Sanctum::actingAs($campaign->foundation->user);
        $fromFoundation = $this->getJson('/api/foundation/donations')->json('0.proof_photo');

        foreach ([$fromDonor, $fromList, $fromFoundation] as $url) {
            // a full address: the app already on phones shows those as they are
            $this->assertStringStartsWith('http', $url);
            $this->assertStringContainsString('signature=', $url);

            $res = $this->get($url)->assertOk();
            $this->assertSame('receipt', $res->streamedContent());
            $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
            $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        }
    }

    public function test_a_donation_without_a_receipt_has_no_link(): void
    {
        $donor = $this->makeUser();
        $donation = Donation::create([
            'campaign_id' => $this->makeCampaign()->id, 'donor_id' => $donor->id, 'type' => 'monetary',
            'amount' => 500, 'status' => 'pending', 'donated_at' => now(),
        ]);

        Sanctum::actingAs($donor);
        $this->getJson("/api/donor/donations/{$donation->id}")->assertOk()->assertJsonPath('proof_photo', null);
    }

    public function test_the_super_admin_gets_links_to_a_foundations_documents(): void
    {
        $this->fakeDisks();
        Storage::disk('local')->put('foundation/admin_ids/id.jpg', 'an id');
        Storage::disk('local')->put('foundation/documents/doc.jpg', 'a document');

        $foundation = $this->makeCampaign()->foundation;
        foreach (['identity' => 'foundation/admin_ids/id.jpg', 'legitimacy' => 'foundation/documents/doc.jpg'] as $type => $path) {
            $foundation->documents()->create(['type' => $type, 'document_type' => 'x', 'file_path' => $path, 'file_name' => 'x', 'file_type' => 'image/jpeg']);
        }

        Sanctum::actingAs($this->makeUser('superadmin'));
        $row = $this->getJson('/api/admin/foundations')->assertOk()->json('0');

        $this->assertSame('an id', $this->get($row['identity_documents'][0]['file_url'])->assertOk()->streamedContent());
        $this->assertSame('a document', $this->get($row['legitimacy_documents'][0]['file_url'])->assertOk()->streamedContent());
    }

    public function test_the_signed_link_is_the_only_way_to_a_private_file(): void
    {
        $this->assertFalse(Route::has('storage.local'));
    }

    public function test_a_file_cannot_be_opened_without_a_signature(): void
    {
        $this->fakeDisks();
        Storage::disk('local')->put('foundation/admin_ids/id.jpg', 'an id');

        $this->get('/api/files/foundation/admin_ids/id.jpg')->assertStatus(403);
    }

    public function test_a_link_cannot_be_changed_to_open_another_file(): void
    {
        $this->fakeDisks();
        Storage::disk('local')->put('donations/proofs/mine.jpg', 'mine');
        Storage::disk('local')->put('foundation/admin_ids/theirs.jpg', 'theirs');

        $url = $this->signed('donations/proofs/mine.jpg');
        $this->get($url)->assertOk();
        $this->get(str_replace('donations/proofs/mine.jpg', 'foundation/admin_ids/theirs.jpg', $url))->assertStatus(403);
    }

    public function test_a_link_stops_working_after_an_hour(): void
    {
        $this->fakeDisks();
        Storage::disk('local')->put('donations/proofs/r.jpg', 'receipt');
        $donor = $this->makeUser();
        $donation = Donation::create([
            'campaign_id' => $this->makeCampaign()->id, 'donor_id' => $donor->id, 'type' => 'monetary', 'amount' => 500,
            'proof_photo' => 'donations/proofs/r.jpg', 'status' => 'pending', 'donated_at' => now(),
        ]);

        Sanctum::actingAs($donor);
        $url = $this->getJson("/api/donor/donations/{$donation->id}")->json('proof_photo');

        $this->travel(59)->minutes();
        $this->get($url)->assertOk();

        $this->travel(2)->minutes();
        $this->get($url)->assertStatus(403);
    }

    public function test_a_signed_link_only_reaches_the_private_folders(): void
    {
        $this->fakeDisks();
        Storage::disk('local')->put('other/secret.txt', 'secret');
        Storage::disk('public')->put('foundations/logo.png', 'logo');

        $this->get($this->signed('other/secret.txt'))->assertStatus(404);
        $this->get($this->signed('foundations/logo.png'))->assertStatus(404);

        $this->assertFalse(PrivateFile::isPrivatePath('donations/proofs/../../../.env'));
        $this->assertFalse(PrivateFile::isPrivatePath('donations/proofs/..\\..\\.env'));
        $this->assertTrue(PrivateFile::isPrivatePath('donations/proofs/r.jpg'));
    }

    public function test_a_missing_file_is_not_found(): void
    {
        $this->fakeDisks();
        $this->get($this->signed('donations/proofs/gone.jpg'))->assertStatus(404);
    }

    public function test_a_file_not_moved_yet_still_opens_with_a_signed_link(): void
    {
        $this->fakeDisks();
        Storage::disk('public')->put('donations/proofs/old.jpg', 'old receipt');

        $this->assertSame('old receipt', $this->get($this->signed('donations/proofs/old.jpg'))->assertOk()->streamedContent());
    }

    public function test_the_move_command_makes_old_files_private_and_leaves_public_pictures(): void
    {
        $this->fakeDisks();
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $public->put('foundation/admin_ids/a.jpg', 'id');
        $public->put('foundation/documents/b.jpg', 'doc');
        $public->put('donations/proofs/c.jpg', 'receipt');
        $public->put('donations/items/d.jpg', 'item');
        $public->put('foundations/e.png', 'logo');

        $this->artisan('files:make-private')->assertSuccessful();

        foreach (['foundation/admin_ids/a.jpg', 'foundation/documents/b.jpg', 'donations/proofs/c.jpg'] as $path) {
            $local->assertExists($path);
            $public->assertMissing($path);
        }
        $this->assertSame('receipt', $local->get('donations/proofs/c.jpg'));
        $public->assertExists('donations/items/d.jpg');
        $public->assertExists('foundations/e.png');
        $local->assertMissing('foundations/e.png');

        // running it again is harmless
        $this->artisan('files:make-private')->assertSuccessful();
        $this->assertSame('receipt', $local->get('donations/proofs/c.jpg'));
    }

    public function test_the_move_command_never_overwrites_a_private_file(): void
    {
        $this->fakeDisks();
        Storage::disk('public')->put('donations/proofs/same.jpg', 'public copy');
        Storage::disk('local')->put('donations/proofs/same.jpg', 'private copy');

        $this->artisan('files:make-private')->assertSuccessful();

        $this->assertSame('private copy', Storage::disk('local')->get('donations/proofs/same.jpg'));
        Storage::disk('public')->assertExists('donations/proofs/same.jpg');
    }

    /* ── A fake X-Forwarded-For can't dodge the limits ── */

    public function test_a_direct_visitor_faking_x_forwarded_for_is_still_locked_at_the_fifth_wrong_try(): void
    {
        $user = $this->makeUser();

        for ($i = 1; $i <= 4; $i++) {
            $this->wrongLogin($user->email, '192.168.1.50', "10.9.8.{$i}")->assertStatus(422);
        }

        $this->wrongLogin($user->email, '192.168.1.50', '10.9.8.5')
            ->assertStatus(429)
            ->assertJsonPath('code', 'login_locked');
    }

    public function test_a_direct_visitor_faking_x_forwarded_for_cannot_flood_reset_emails(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
                ->postJson('/api/forgot-password', ['email' => $user->email], ['X-Forwarded-For' => "10.7.7.{$i}"])
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->postJson('/api/forgot-password', ['email' => $user->email], ['X-Forwarded-For' => '10.7.7.6'])
            ->assertStatus(429);

        Notification::assertCount(5);
    }

    public function test_behind_the_local_proxy_the_real_visitor_counts_not_the_fake_value_in_front(): void
    {
        $user = $this->makeUser();

        // ngrok connects from this computer and adds the real visitor last
        for ($i = 1; $i <= 4; $i++) {
            $this->wrongLogin($user->email, '127.0.0.1', "10.9.8.{$i}, 203.0.113.9")->assertStatus(422);
        }
        $this->wrongLogin($user->email, '127.0.0.1', '10.9.8.5, 203.0.113.9')->assertStatus(429);

        // someone else behind the same proxy is not locked
        $this->wrongLogin($user->email, '127.0.0.1', '203.0.113.10')->assertStatus(422);
    }

    public function test_a_stranger_cannot_claim_to_be_the_https_proxy(): void
    {
        $html = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example.com'])
            ->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('evil.example.com', $html);
    }

    public function test_a_host_with_its_own_front_server_can_be_trusted_through_the_setting(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $html = $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'foundationlink.example.net', 'X-Forwarded-Port' => '443'])
            ->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('https://foundationlink.example.net/build/', $html);
    }

    /* ── Limits per email, whatever the address ── */

    public function test_many_addresses_together_are_stopped_after_thirty_wrong_passwords(): void
    {
        $user = $this->makeUser();

        for ($i = 1; $i <= 30; $i++) {
            $this->wrongLogin($user->email, "198.51.100.{$i}")->assertStatus(422);
        }

        // even the right password waits, from a brand-new address
        $this->wrongLogin($user->email, '198.51.100.200', null, self::PASSWORD)
            ->assertStatus(429)
            ->assertJsonPath('code', 'login_locked')
            ->assertJsonPath('show_support', true);

        // an hour's worth of waiting is not needed: it opens again after 15 minutes
        $this->travel(16)->minutes();
        $this->wrongLogin($user->email, '198.51.100.200', null, self::PASSWORD)->assertOk();
    }

    public function test_a_successful_login_clears_the_count_for_that_email(): void
    {
        $user = $this->makeUser();

        for ($i = 1; $i <= 29; $i++) {
            $this->wrongLogin($user->email, "198.51.100.{$i}")->assertStatus(422);
        }
        $this->wrongLogin($user->email, '198.51.100.200', null, self::PASSWORD)->assertOk();

        for ($i = 101; $i <= 110; $i++) {
            $this->wrongLogin($user->email, "198.51.100.{$i}")->assertStatus(422);
        }
    }

    public function test_one_address_alone_cannot_lock_the_real_owner_out(): void
    {
        $user = $this->makeUser();

        // the attacker's address locks itself first: 5 tries, then waits
        for ($i = 0; $i < 4; $i++) {
            $this->wrongLogin($user->email, '198.51.100.66')->assertStatus(422);
        }
        $this->wrongLogin($user->email, '198.51.100.66')->assertStatus(429);

        // the owner, somewhere else, logs in normally
        $this->wrongLogin($user->email, '203.0.113.5', null, self::PASSWORD)->assertOk();
    }

    public function test_one_email_gets_at_most_fifteen_reset_emails_an_hour_from_any_addresses(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        for ($i = 1; $i <= 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$i}"])
                ->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.200'])
            ->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(429);

        Notification::assertCount(15);

        // somebody else's email is not affected
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.200'])
            ->postJson('/api/forgot-password', ['email' => $this->makeUser()->email])->assertOk();
    }

    /* ── Sign-up limit ── */

    public function test_sign_ups_are_limited_per_minute_with_a_clear_message(): void
    {
        foreach (['/api/register/donor', '/api/foundation-admin/register'] as $url) {
            for ($i = 0; $i < 10; $i++) {
                $this->signUp($url, "198.51.100.{$this->addressFor($url)}")->assertStatus(422);
            }
            $this->signUp($url, "198.51.100.{$this->addressFor($url)}")
                ->assertStatus(429)
                ->assertJsonPath('message', 'Too many sign-up attempts from this network. Please wait a few minutes and try again.');
        }
    }

    private function addressFor(string $url): int
    {
        return $url === '/api/register/donor' ? 1 : 2;
    }

    public function test_sign_ups_are_limited_per_hour(): void
    {
        for ($batch = 0; $batch < 6; $batch++) {
            for ($i = 0; $i < 10; $i++) {
                $this->signUp('/api/register/donor')->assertStatus(422);
            }
            $this->travel(61)->seconds();
        }

        $this->signUp('/api/register/donor')->assertStatus(429);

        $this->travel(1)->hours();
        $this->signUp('/api/register/donor')->assertStatus(422);
    }

    public function test_the_sign_up_limit_is_per_address_and_real_sign_ups_still_work(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->signUp('/api/register/donor')->assertStatus(422);
        }
        $this->signUp('/api/register/donor')->assertStatus(429);

        // another network can still sign up, for real
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])->postJson('/api/register/donor', [
            'first_name' => 'Real', 'last_name' => 'Person', 'email' => 'real@example.com', 'phone' => '+639171234570',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ])->assertSuccessful();
        $this->assertSame(1, User::where('email', 'real@example.com')->count());
    }

    /* ── Login expiry and password changes ── */

    public function test_a_login_expires_after_thirty_days(): void
    {
        $token = $this->makeUser()->createToken('auth_token')->plainTextToken;

        $this->travel(29)->days();
        $this->withToken($token)->getJson('/api/user')->assertOk();

        app('auth')->forgetGuards();
        $this->travel(2)->days();
        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
    }

    public function test_a_donor_changing_their_password_logs_out_other_devices_only(): void
    {
        $donor = $this->makeUser();
        $stolen = $donor->createToken('auth_token')->plainTextToken;
        $thisPhone = $donor->createToken('auth_token')->plainTextToken;

        $this->withToken($thisPhone)->postJson('/api/donor/profile', [
            'current_password' => self::PASSWORD, 'password' => 'Brand-new-pass-1!', 'password_confirmation' => 'Brand-new-pass-1!',
        ])->assertOk();

        app('auth')->forgetGuards();
        $this->withToken($thisPhone)->getJson('/api/user')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($stolen)->getJson('/api/user')->assertStatus(401);
    }

    public function test_a_foundation_admin_changing_their_password_logs_out_other_devices_only(): void
    {
        $admin = $this->makeUser('foundation_admin');
        Foundation::create(['user_id' => $admin->id, 'name' => 'Mine', 'status' => 'verified']);
        $stolen = $admin->createToken('auth_token')->plainTextToken;
        $thisLaptop = $admin->createToken('auth_token')->plainTextToken;

        $this->withToken($thisLaptop)->postJson('/api/foundation/profile', [
            'current_password' => self::PASSWORD, 'new_password' => 'Brand-new-pass-1!', 'new_password_confirmation' => 'Brand-new-pass-1!',
        ])->assertOk();

        app('auth')->forgetGuards();
        $this->withToken($thisLaptop)->getJson('/api/user')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($stolen)->getJson('/api/user')->assertStatus(401);
    }

    public function test_editing_a_profile_without_a_new_password_keeps_other_devices_logged_in(): void
    {
        $donor = $this->makeUser();
        $otherPhone = $donor->createToken('auth_token')->plainTextToken;
        $thisPhone = $donor->createToken('auth_token')->plainTextToken;

        $this->withToken($thisPhone)->postJson('/api/donor/profile', ['first_name' => 'Renamed'])->assertOk();

        app('auth')->forgetGuards();
        $this->withToken($otherPhone)->getJson('/api/user')->assertOk();
    }

    public function test_a_wrong_current_password_logs_nobody_out(): void
    {
        $donor = $this->makeUser();
        $otherPhone = $donor->createToken('auth_token')->plainTextToken;
        $thisPhone = $donor->createToken('auth_token')->plainTextToken;

        $this->withToken($thisPhone)->postJson('/api/donor/profile', [
            'current_password' => 'Not-my-pass-123!', 'password' => 'Brand-new-pass-1!', 'password_confirmation' => 'Brand-new-pass-1!',
        ])->assertStatus(422);

        app('auth')->forgetGuards();
        $this->withToken($otherPhone)->getJson('/api/user')->assertOk();
    }

    /* ── Suspended foundations ── */

    public function test_only_a_verified_foundation_can_schedule_a_pick_up(): void
    {
        $donor = $this->makeUser();
        $campaign = $this->makeCampaign();
        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $donor->id, 'type' => 'item', 'item_name' => 'Rice', 'item_quantity' => 1,
            'delivery_method' => 'pickup', 'delivery_address' => 'Blue gate', 'status' => 'pending', 'donated_at' => now(),
        ]);
        $payload = ['date' => now(config('app.display_timezone'))->addDay()->toDateString(), 'window' => 'morning'];
        Sanctum::actingAs($campaign->foundation->user);

        foreach (['suspended', 'rejected', 'under_review'] as $status) {
            $campaign->foundation->update(['status' => $status]);
            $this->patchJson("/api/foundation/donations/{$donation->id}/pickup-schedule", $payload)
                ->assertStatus(403)
                ->assertJsonPath('message', 'Your foundation must be verified before managing donations.');
            $this->assertNull($donation->fresh()->pickup_date);
        }

        $campaign->foundation->update(['status' => 'verified']);
        $this->patchJson("/api/foundation/donations/{$donation->id}/pickup-schedule", $payload)->assertOk();
        $this->assertNotNull($donation->fresh()->pickup_date);
    }
}
