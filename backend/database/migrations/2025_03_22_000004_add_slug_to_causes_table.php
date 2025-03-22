<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Cause;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            if (!Schema::hasColumn('causes', 'slug')) {
                $table->string('slug')->unique()->after('title')->nullable();
            }
        });
        
        // Generate slugs for existing causes
        $causes = Cause::all();
        foreach ($causes as $cause) {
            $cause->slug = Str::slug($cause->title);
            $cause->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('causes', function (Blueprint $table) {
            if (Schema::hasColumn('causes', 'slug')) {
                $table->dropColumn('slug');
            }
        });
    }
}; 