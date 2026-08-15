<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('foundation_documents', function (Blueprint $table) {

            $table->id();

            // Link to foundation
            $table->foreignId('foundation_id')
                ->constrained('foundations')
                ->onDelete('cascade');

            /**
             * TYPE OF DOCUMENT GROUP
             * identity = admin ID (passport, national ID, etc.)
             * legitimacy = foundation legal documents (SEC, DSWD, etc.)
             */
            $table->enum('type', ['identity', 'legitimacy']);

            /**
             * SPECIFIC DOCUMENT TYPE
             * Example:
             * identity -> National ID, Passport, Driver's License
             * legitimacy -> SEC, DSWD, CDA, Government Charter, etc.
             */
            $table->string('document_type');

            /**
             * FILE PATH (stored in storage/app/public)
             */
            $table->string('file_path');

            /**
             * ORIGINAL FILE NAME (optional but useful)
             */
            $table->string('file_name')->nullable();

            /**
             * FILE TYPE (jpg, png, pdf, etc.)
             */
            $table->string('file_type')->nullable();

            /**
             * VERIFICATION STATUS
             */
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            /**
             * ADMIN REMARKS (if rejected or needs correction)
             */
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foundation_documents');
    }
};