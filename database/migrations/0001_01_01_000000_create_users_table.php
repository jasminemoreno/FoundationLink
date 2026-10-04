<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('pending_email')->nullable();
            $table->string('email_change_token')->nullable();
            $table->timestamp('email_change_expires_at')->nullable();
            $table->string('password');

            $table->enum('role', [
                'donor',
                'foundation_admin',
                'superadmin'
            ]);

            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('gender', ['male', 'female', 'prefer_not_to_say'])->nullable();
            $table->date('birthdate')->nullable();
            $table->string('profile_photo')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
                'suspended'
            ])->default('active');

            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};