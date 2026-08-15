<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foundation_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('foundation_id')->constrained('foundations')->onDelete('cascade');
            $table->enum('type', ['follow', 'like'])->default('follow');
            $table->timestamps();

            $table->unique(['donor_id', 'foundation_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_followers');
    }
};