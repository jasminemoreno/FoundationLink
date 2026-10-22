<?php

use App\Support\CategoryIcon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Categories created before the bigger icon list got the 📁 folder whenever
 * their name wasn't one of 9 keywords (e.g. "Elderly Care"). Give those a
 * fitting icon now. Only rows still showing exactly 📁 are touched; an icon
 * someone chose is left alone.
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('categories')->where('icon', '📁')->orderBy('id')->each(function ($category) {
            DB::table('categories')
                ->where('id', $category->id)
                ->update(['icon' => CategoryIcon::for($category->name), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        // Not reversed: the folder icon was a gap, not a choice anyone made.
    }
};
