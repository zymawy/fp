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
        Schema::table('partners', function (Blueprint $table) {
            $table->text('description')->nullable()->after('logo');
            
            // Also check if 'logo' column is nullable, if not make it nullable
            // as we're allowing upload with no logo in the form
            if (Schema::hasColumn('partners', 'logo')) {
                $table->string('logo')->nullable()->change();
            }
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('partners', 'status')) {
                $table->string('status')->default('active')->after('is_featured');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('description');
            
            // Drop status column if we added it
            if (Schema::hasColumn('partners', 'status')) {
                $table->dropColumn('status');
            }
            
            // We can't revert the logo column to not nullable in a safe way
        });
    }
}; 