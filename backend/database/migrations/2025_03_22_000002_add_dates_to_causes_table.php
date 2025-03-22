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
            // Add start_date column if it doesn't exist
            if (!Schema::hasColumn('causes', 'start_date')) {
                $table->timestamp('start_date')->nullable()->after('status');
            }
            
            // Add end_date column if it doesn't exist
            if (!Schema::hasColumn('causes', 'end_date')) {
                $table->timestamp('end_date')->nullable()->after('start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('causes', 'start_date')) {
                $table->dropColumn('start_date');
            }
            
            if (Schema::hasColumn('causes', 'end_date')) {
                $table->dropColumn('end_date');
            }
        });
    }
}; 