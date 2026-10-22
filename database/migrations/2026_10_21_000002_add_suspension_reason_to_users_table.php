<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds users.suspension_reason to databases created before it was added to the
 * base users migration. Fresh installs already have it, so this is a no-op there.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'suspension_reason')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('suspension_reason')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        // Left in place: the base users migration owns this column on fresh installs.
    }
};
