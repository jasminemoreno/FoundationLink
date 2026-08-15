<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // e.g. GCash
            $table->string('slug')->unique();        // e.g. gcash
            $table->string('icon')->nullable();      // emoji or image path
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default payment methods
        DB::table('payment_methods')->insert([
            ['name' => 'GCash', 'slug' => 'gcash', 'icon' => '📱', 'description' => 'GCash mobile wallet', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PayMaya', 'slug' => 'paymaya', 'icon' => '💳', 'description' => 'PayMaya digital wallet', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PayPal', 'slug' => 'paypal', 'icon' => '🌐', 'description' => 'PayPal online payment', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'BDO', 'slug' => 'bdo', 'icon' => '🏦', 'description' => 'BDO bank transfer', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'BPI', 'slug' => 'bpi', 'icon' => '🏦', 'description' => 'BPI bank transfer', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};