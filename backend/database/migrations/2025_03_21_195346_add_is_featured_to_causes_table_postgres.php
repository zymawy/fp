<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('causes', 'is_featured')) {
            Schema::table('causes', function (Blueprint $table) {
                $table->boolean('is_featured')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('causes', 'is_featured')) {
            Schema::table('causes', function (Blueprint $table) {
                $table->dropColumn('is_featured');
            });
        }
    }
};
