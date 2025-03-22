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
            // Add image column if it doesn't exist
            if (!Schema::hasColumn('causes', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            
            // Rename target_amount and collected_amount if needed
            if (Schema::hasColumn('causes', 'target_amount') && !Schema::hasColumn('causes', 'goal_amount')) {
                $table->renameColumn('target_amount', 'goal_amount');
            }
            
            if (Schema::hasColumn('causes', 'collected_amount') && !Schema::hasColumn('causes', 'raised_amount')) {
                $table->renameColumn('collected_amount', 'raised_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            // Drop image column if it exists
            if (Schema::hasColumn('causes', 'image')) {
                $table->dropColumn('image');
            }
            
            // Reverse column renames if needed
            if (Schema::hasColumn('causes', 'goal_amount') && !Schema::hasColumn('causes', 'target_amount')) {
                $table->renameColumn('goal_amount', 'target_amount');
            }
            
            if (Schema::hasColumn('causes', 'raised_amount') && !Schema::hasColumn('causes', 'collected_amount')) {
                $table->renameColumn('raised_amount', 'collected_amount');
            }
        });
    }
}; 