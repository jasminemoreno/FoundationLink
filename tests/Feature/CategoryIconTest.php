<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\CategoryIcon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Category icons: picked from the name (English and Filipino), or chosen by
 * the super admin. A name with no match gets a helping-hands icon, not a folder.
 */
class CategoryIconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'superadmin']));
    }

    public function test_common_category_names_get_a_fitting_icon(): void
    {
        $expected = [
            // the three that showed a folder in the real database
            'Community Development' => '🏘️',
            'Elderly Care' => '👵',
            'Poverty Alleviation' => '🤲',
            // the ones that already worked keep their icon
            'Children & Youth' => '🧒',
            'Animal Welfare' => '🐶',
            'Education' => '🎓',
            'Health' => '🏥',
            'Environment' => '🌱',
            'Disaster Relief' => '🌪️',
            // more names a foundation platform will see
            'Livelihood Programs' => '💼',
            'Clean Water' => '💧',
            'Housing & Shelter' => '🏠',
            'Women Empowerment' => '👩',
            'Persons with Disability' => '♿',
            'Mental Health' => '🧠',
            'Sports Development' => '⚽',
            'Arts and Culture' => '🎨',
            'Faith-based Missions' => '⛪',
            'Feeding Program' => '🍲',
            'Medical Assistance' => '💊',
            'Scholarship Fund' => '🎓',
            'Educational Support' => '🎓',   // word forms
            'Tree Planting' => '🌳',
            'Coastal Cleanup' => '🌊',
        ];

        foreach ($expected as $name => $icon) {
            $this->assertSame($icon, CategoryIcon::for($name), "Wrong icon for \"{$name}\"");
        }
    }

    public function test_filipino_names_get_a_fitting_icon(): void
    {
        $expected = [
            'Edukasyon' => '🎓',
            'Kalusugan' => '🏥',
            'Pagkain para sa Lahat' => '🍲',
            'Kalikasan' => '🌱',
            'Kapakanan ng Hayop' => '🐶',
            'Kabataan' => '🧒',
            'Mga Nakatatanda' => '👵',
            'Komunidad' => '🏘️',
            'Kahirapan' => '🤲',
            'Kabuhayan' => '💼',
            'Tulong sa Sakuna' => '🌪️',
            'Malinis na Tubig' => '💧',
            'Pabahay' => '🏠',
        ];

        foreach ($expected as $name => $icon) {
            $this->assertSame($icon, CategoryIcon::for($name), "Wrong icon for \"{$name}\"");
        }
    }

    public function test_whole_words_only_so_heart_is_not_art(): void
    {
        $this->assertNotSame('🎨', CategoryIcon::for('Heart Foundation'));
        $this->assertNotSame('🐶', CategoryIcon::for('Pethidine Research')); // "pet" inside another word
    }

    public function test_a_name_with_no_match_gets_helping_hands_not_a_folder(): void
    {
        $this->assertSame('🤝', CategoryIcon::for('Miscellaneous'));
        $this->assertNotSame('📁', CategoryIcon::for('Something New'));
    }

    public function test_creating_a_category_picks_the_icon_or_uses_the_chosen_one(): void
    {
        $this->postJson('/api/admin/categories', ['name' => 'Elderly Care'])
            ->assertStatus(201)->assertJsonPath('category.icon', '👵');

        $this->postJson('/api/admin/categories', ['name' => 'Elderly Care Two', 'icon' => '❤️'])
            ->assertStatus(201)->assertJsonPath('category.icon', '❤️');
    }

    public function test_renaming_updates_an_automatic_icon_but_keeps_a_chosen_one(): void
    {
        $auto = Category::create(['name' => 'Health', 'icon' => '🏥']);
        $this->putJson("/api/admin/categories/{$auto->id}", ['name' => 'Clean Water'])->assertOk();
        $this->assertSame('💧', $auto->fresh()->icon);

        $chosen = Category::create(['name' => 'Health', 'icon' => '❤️']);
        $this->putJson("/api/admin/categories/{$chosen->id}", ['name' => 'Clean Water'])->assertOk();
        $this->assertSame('❤️', $chosen->fresh()->icon);
    }

    public function test_suggested_icon_endpoint_previews_the_automatic_icon(): void
    {
        $this->getJson('/api/admin/categories/suggest-icon?name=Elderly%20Care')
            ->assertOk()->assertJsonPath('icon', '👵');
    }
}
