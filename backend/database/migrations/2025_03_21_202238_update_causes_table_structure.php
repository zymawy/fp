<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the 'category' column exists (old structure) or 'category_id' exists (new structure)
        $hasCategory = Schema::hasColumn('causes', 'category');
        $hasCategoryId = Schema::hasColumn('causes', 'category_id');
        
        // First, update the media_url for all causes to use Unsplash
        $categories = DB::table('categories')->get(['id', 'name'])->keyBy('name');
        $causes = DB::table('causes')->get();
        
        foreach ($causes as $cause) {
            // Check if category is a string (category name)
            $categoryName = null;
            
            if ($hasCategory && is_string($cause->category ?? null)) {
                $categoryName = $cause->category;
            } else if ($hasCategoryId) {
                // Attempt to get category name by ID
                $categoryName = DB::table('categories')
                    ->where('id', $cause->category_id ?? null)
                    ->value('name');
            }
            
            // Default to 'charity' if no category name is found
            $categoryName = $categoryName ?? 'charity';
            
            // Update the media URL
            $mediaUrl = "https://source.unsplash.com/800x600/?" . urlencode($categoryName);
            
            DB::table('causes')
                ->where('id', $cause->id)
                ->update(['media_url' => $mediaUrl]);
        }
        
        // If the old structure exists, migrate it to the new structure
        if ($hasCategory && !$hasCategoryId) {
            // Create a category_id_temp column
            Schema::table('causes', function (Blueprint $table) {
                $table->uuid('category_id_temp')->nullable();
            });
            
            // Transfer data from category to category_id_temp with category lookup
            foreach ($causes as $cause) {
                if (is_string($cause->category ?? null) && isset($categories[$cause->category])) {
                    // If category is a name (string), find the corresponding category ID
                    $categoryId = $categories[$cause->category]->id;
                    DB::table('causes')
                        ->where('id', $cause->id)
                        ->update(['category_id_temp' => $categoryId]);
                }
            }
            
            // Rename the existing column to category_old
            Schema::table('causes', function (Blueprint $table) {
                $table->renameColumn('category', 'category_old');
            });
            
            // Rename temp column to category_id
            Schema::table('causes', function (Blueprint $table) {
                $table->renameColumn('category_id_temp', 'category_id');
            });
            
            // Add foreign key constraint
            Schema::table('causes', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->onDelete('cascade');
            });
            
            // Drop the old column
            Schema::table('causes', function (Blueprint $table) {
                $table->dropColumn('category_old');
            });
        } else {
            // Structure is already correct, log the information
            Log::info('The causes table already has the correct structure with category_id');
        }
        
        // Ensure the is_featured column exists
        if (!Schema::hasColumn('causes', 'is_featured')) {
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
        // We can't easily reverse this migration since it involves data transformation
        // But we can remove the foreign key and create a category column again
        if (Schema::hasColumn('causes', 'category_id')) {
            Schema::table('causes', function (Blueprint $table) {
                if (Schema::hasColumn('causes', 'category')) {
                    $table->dropColumn('category');
                }
                
                try {
                    $table->dropForeign(['category_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore this error
                }
                
                $table->string('category')->nullable();
            });
        }
    }
};
