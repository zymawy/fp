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
        Schema::create('cause_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cause_id');
            $table->decimal('total_donations', 10, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('cause_id')
                  ->references('id')
                  ->on('causes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cause_reports');
    }
};
