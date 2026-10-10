<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foundations', function (Blueprint $table) {
            $table->id();

            // 👤 Foundation owner/admin
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 🏷️ Foundation category (Health, Education, etc.)
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();

            // 🏛️ Basic info
            $table->string('name');
            $table->text('description')->nullable();

            // 📍 Address (structured but simple)
            $table->string('street')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('province')->nullable();

            // 🖼️ Branding
            $table->string('logo')->nullable();
            $table->string('cover_photo')->nullable();

            // ⚙️ Status flow (approval system)
            $table->enum('status', [
                'incomplete',
                'pending_verification',
                'under_review',
                'verified',
                'rejected'
            ])->default('pending_verification');

            // ❌ If rejected by admin
            $table->text('rejection_reason')->nullable();

            // ✅ When verified
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundations');
    }
};