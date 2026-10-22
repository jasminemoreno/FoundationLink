<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignPhoto;
use App\Models\CampaignUpdate;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\FoundationPaymentAccount;
use App\Models\Notification;
use App\Models\PaymentMethod;
use App\Models\UpdateReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Calls every GET endpoint in routes/api.php as the role that owns it, against a
 * realistic data set, and requires a successful response. Routes are discovered
 * automatically, so endpoints added later are covered without editing this file.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, int> route parameter name => id to use */
    private array $params = [];

    protected function setUp(): void
    {
        parent::setUp();

        $super = User::factory()->create(['role' => 'superadmin']);
        $admin = User::factory()->create(['role' => 'foundation_admin']);
        $donor = User::factory()->create(['role' => 'donor']);
        $this->users = ['admin' => $super, 'foundation' => $admin, 'donor' => $donor];

        $category = Category::create(['name' => 'Education']);
        $foundation = Foundation::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'name' => 'Helping Hands',
            'status' => 'verified',
            'verified_at' => now(),
        ]);
        $gcash = PaymentMethod::firstOrCreate(['slug' => 'gcash'], ['name' => 'GCash', 'is_active' => true]);
        FoundationPaymentAccount::create([
            'foundation_id' => $foundation->id,
            'payment_method_id' => $gcash->id,
            'account_name' => 'Helping Hands',
            'account_number' => '09170000000',
        ]);

        $campaign = Campaign::create([
            'foundation_id' => $foundation->id,
            'category_id' => $category->id,
            'created_by' => $admin->id,
            'title' => 'School Supplies Drive',
            'description' => 'Notebooks and pens',
            'type' => 'both',
            'goal_amount' => 10000,
            'current_amount' => 500,
            'start_date' => now()->subWeek(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
            'accepted_delivery_methods' => ['pickup', 'dropoff'],
            'accepted_payment_methods' => [(string) $gcash->id],
        ]);
        CampaignPhoto::create(['campaign_id' => $campaign->id, 'photo_path' => 'campaigns/photos/a.jpg', 'order' => 0]);

        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $donor->id, 'type' => 'monetary',
            'amount' => 500, 'payment_method_id' => $gcash->id, 'status' => 'received', 'donated_at' => now(),
        ]);
        Donation::create([
            'campaign_id' => $campaign->id, 'donor_id' => $donor->id, 'type' => 'item',
            'item_name' => 'Notebook', 'item_quantity' => 10, 'delivery_method' => 'dropoff',
            'status' => 'pending', 'donated_at' => now(),
        ]);

        $update = CampaignUpdate::create(['campaign_id' => $campaign->id, 'title' => 'Thank you!', 'photos' => []]);
        UpdateReaction::create(['donor_id' => $donor->id, 'campaign_update_id' => $update->id]);
        foreach (['follow', 'like'] as $type) {
            DB::table('foundation_followers')->insert([
                'donor_id' => $donor->id, 'foundation_id' => $foundation->id, 'type' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->users as $user) {
            Notification::create([
                'user_id' => $user->id, 'title' => 'Hello', 'message' => 'Test notification',
                'type' => 'campaign_update', 'notifiable_id' => $update->id, 'notifiable_type' => CampaignUpdate::class,
            ]);
        }

        // Named route parameters, and {id} resolved by the path segment before it.
        $this->params = [
            'campaignId' => $campaign->id,
            'updateId' => $update->id,
            'paymentMethodId' => $gcash->id,
        ];
        $this->ids = [
            'foundations' => $foundation->id,
            'categories' => $category->id,
            'users' => $donor->id,
            'payment-methods' => $gcash->id,
            'campaigns' => $campaign->id,
            'donations' => $donation->id,
        ];
    }

    private array $ids = [];

    /** Fill {params} in a route URI with ids from the seeded data. */
    private function fillUri(string $uri): ?string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($m) use ($uri) {
            $name = $m[1];
            if (isset($this->params[$name])) {
                return $this->params[$name];
            }
            if ($name === 'id') {
                // The resource is the path segment just before {id}.
                preg_match('#([\w-]+)/\{id\}#', $uri, $seg);
                return $this->ids[$seg[1]] ?? 'MISSING';
            }
            return 'MISSING';
        }, $uri);
    }

    public function test_every_get_endpoint_responds_successfully_for_its_role(): void
    {
        $checked = 0;
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/') || !in_array('GET', $route->methods(), true)) {
                continue;
            }
            // Email-change links are token-based; covered by their own flow, not a seeded id.
            if (str_contains($uri, '{token}')) {
                continue;
            }

            $area = explode('/', $uri)[1];
            $user = $this->users[$area] ?? $this->users['donor'];
            $url = '/' . $this->fillUri($uri);

            if (str_contains($url, 'MISSING')) {
                $failures[] = "{$uri}: no test id for a route parameter";
                continue;
            }

            Sanctum::actingAs($user);
            $response = $this->getJson($url);
            $checked++;

            if ($response->status() !== 200) {
                $failures[] = "GET {$url} as {$user->role} -> {$response->status()}: "
                    . mb_substr((string) ($response->json('message') ?? $response->getContent()), 0, 200);
            }
        }

        $this->assertGreaterThan(30, $checked, 'Expected to discover the API GET routes.');
        $this->assertSame([], $failures, "Endpoints that failed:\n" . implode("\n", $failures));
    }
}
