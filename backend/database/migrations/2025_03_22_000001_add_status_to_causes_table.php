<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('causes', 'status')) {
                $table->string('status')->default('active')->after('category_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            // Drop status column if it exists
            if (Schema::hasColumn('causes', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}; 