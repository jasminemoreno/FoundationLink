<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Donation;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Report filters: the super admin can narrow the report to one foundation or one
 * foundation category, and a foundation admin to one of their campaigns.
 * The filters combine with the From / To dates.
 */
class ReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $adminA;
    private User $donor1;
    private User $donor2;
    private Foundation $helping;   // Education
    private Foundation $green;     // Environment
    private Campaign $books;       // Helping Hands
    private Campaign $meals;       // Helping Hands
    private Campaign $trees;       // Green Earth
    private Category $education;
    private Category $environment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->adminA = User::factory()->create(['role' => 'foundation_admin']);
        $adminB = User::factory()->create(['role' => 'foundation_admin']);
        $this->donor1 = User::factory()->create(['role' => 'donor', 'first_name' => 'Ana', 'last_name' => 'Cruz']);
        $this->donor2 = User::factory()->create(['role' => 'donor', 'first_name' => 'Ben', 'last_name' => 'Reyes']);

        $this->education = Category::create(['name' => 'Education']);
        $this->environment = Category::create(['name' => 'Environment']);

        $this->helping = Foundation::create(['user_id' => $this->adminA->id, 'name' => 'Helping Hands', 'status' => 'verified', 'category_id' => $this->education->id]);
        $this->green = Foundation::create(['user_id' => $adminB->id, 'name' => 'Green Earth', 'status' => 'verified', 'category_id' => $this->environment->id]);

        $make = fn(Foundation $f, User $admin, string $title) => Campaign::create([
            'foundation_id' => $f->id, 'created_by' => $admin->id, 'title' => $title,
            'description' => '', 'type' => 'both', 'goal_amount' => 10000, 'status' => 'active',
        ]);
        $this->books = $make($this->helping, $this->adminA, 'Books for Kids');
        $this->meals = $make($this->helping, $this->adminA, 'Meal Packs');
        $this->trees = $make($this->green, $adminB, 'Plant Trees');

        $this->give($this->books, $this->donor1, 1000);
        $this->give($this->meals, $this->donor1, 300);
        $this->give($this->meals, $this->donor2, 0, 'item');
        $this->give($this->trees, $this->donor2, 5000);
    }

    private function give(Campaign $c, User $donor, float $amount, string $type = 'monetary', string $status = 'received'): void
    {
        Donation::create([
            'campaign_id' => $c->id, 'donor_id' => $donor->id, 'type' => $type,
            'amount' => $type === 'monetary' ? $amount : null, 'item_name' => $type === 'item' ? 'Rice' : null,
            'status' => $status, 'donated_at' => now()->subDays(2),
        ]);
    }

    private function report(array $params = [])
    {
        Sanctum::actingAs($this->superadmin);

        return $this->getJson('/api/admin/reports?' . http_build_query($params))->assertOk();
    }

    public function test_super_admin_can_narrow_the_report_to_one_foundation(): void
    {
        $res = $this->report(['foundation_id' => $this->helping->id])
            ->assertJsonPath('filters.foundation', 'Helping Hands')
            ->assertJsonPath('kpis.0.value', '₱1,300')
            ->assertJsonPath('kpis.1.value', '₱650')
            ->assertJsonPath('kpis.2.label', 'Donors')
            ->assertJsonPath('kpis.2.value', '2')        // Ana (money) and Ben (item)
            ->assertJsonPath('kpis.3.value', '1')
            ->assertJsonPath('top_donors.0.name', 'Ana Cruz')
            ->assertJsonCount(1, 'top_donors')           // Ben gave Helping Hands only an item
            ->assertJsonCount(1, 'top_foundations')
            ->assertJsonPath('top_foundations.0.raised', '₱1,300')
            ->assertJsonPath('categories.0.cat', 'Education')
            ->assertJsonPath('categories.0.pct', 100);

        $this->assertEquals(1300, collect($res->json('monthly'))->sum('raised'));
    }

    public function test_super_admin_can_narrow_the_report_to_one_category(): void
    {
        $this->report(['category_id' => $this->environment->id])
            ->assertJsonPath('filters.category', 'Environment')
            ->assertJsonPath('kpis.0.value', '₱5,000')
            ->assertJsonPath('top_donors.0.name', 'Ben Reyes')
            ->assertJsonCount(1, 'top_foundations')
            ->assertJsonPath('top_foundations.0.name', 'Green Earth');
    }

    public function test_no_filter_means_everything_and_the_dropdowns_get_their_choices(): void
    {
        $this->report()
            ->assertJsonPath('filters.foundation', null)
            ->assertJsonPath('kpis.0.value', '₱6,300')
            ->assertJsonPath('kpis.2.label', 'New Donors')
            ->assertJsonPath('options.foundations.0.name', 'Green Earth')   // sorted by name
            ->assertJsonPath('options.foundations.1.name', 'Helping Hands')
            ->assertJsonPath('options.categories.0.name', 'Education');
    }

    public function test_filters_combine_with_the_dates(): void
    {
        Donation::create([
            'campaign_id' => $this->books->id, 'donor_id' => $this->donor2->id, 'type' => 'monetary',
            'amount' => 700, 'status' => 'received', 'donated_at' => now()->subYear(),
        ]);

        $this->report([
            'foundation_id' => $this->helping->id,
            'from' => now(config('app.display_timezone'))->subDays(7)->toDateString(),
            'to' => now(config('app.display_timezone'))->toDateString(),
        ])->assertJsonPath('kpis.0.value', '₱1,300');   // last year's ₱700 is left out
    }

    public function test_unknown_foundation_or_category_is_refused(): void
    {
        Sanctum::actingAs($this->superadmin);
        $this->getJson('/api/admin/reports?foundation_id=9999')->assertStatus(422)->assertJsonValidationErrors('foundation_id');
        $this->getJson('/api/admin/reports?category_id=9999')->assertStatus(422)->assertJsonValidationErrors('category_id');
    }

    public function test_foundation_admin_can_narrow_the_report_to_one_campaign(): void
    {
        Sanctum::actingAs($this->adminA);

        $this->getJson("/api/foundation/reports?campaign_id={$this->meals->id}")->assertOk()
            ->assertJsonPath('filters.campaign', 'Meal Packs')
            ->assertJsonPath('summary.total_raised', 300)
            ->assertJsonPath('summary.total_items', 1)
            ->assertJsonPath('summary.total_donors', 2)
            ->assertJsonPath('summary.total_campaigns', 1)
            ->assertJsonCount(1, 'per_campaign')
            ->assertJsonPath('per_campaign.0.title', 'Meal Packs')
            ->assertJsonCount(2, 'campaign_options')       // the dropdown still lists every campaign
            ->assertJsonPath('campaign_options.0.title', 'Books for Kids');

        // No filter: the whole foundation
        $this->getJson('/api/foundation/reports')->assertOk()
            ->assertJsonPath('filters.campaign', null)
            ->assertJsonPath('summary.total_raised', 1300)
            ->assertJsonCount(2, 'per_campaign');
    }

    public function test_a_foundation_admin_cannot_report_on_another_foundations_campaign(): void
    {
        Sanctum::actingAs($this->adminA);

        $this->getJson("/api/foundation/reports?campaign_id={$this->trees->id}")
            ->assertStatus(422)->assertJsonValidationErrors('campaign_id');
    }
}
