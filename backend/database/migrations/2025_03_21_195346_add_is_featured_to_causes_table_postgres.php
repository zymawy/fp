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
        // Using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE causes ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT false');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Using raw SQL for PostgreSQL
        DB::statement('ALTER TABLE causes DROP COLUMN IF EXISTS is_featured');
    }
};
