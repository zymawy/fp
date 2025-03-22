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
        // Update all partner logos to use Unsplash images
        $partners = DB::table('partners')->get();
        
        foreach ($partners as $partner) {
            // Create a unique, relevant Unsplash image URL based on the partner name
            $logoUrl = "https://source.unsplash.com/400x200/?company,logo," . urlencode($partner->name);
            
            DB::table('partners')
                ->where('id', $partner->id)
                ->update(['logo' => $logoUrl]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only updates data, not structure
        // No specific down action required
    }
};
