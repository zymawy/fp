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
        Schema::create('cause_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cause_id');
            $table->string('title');
            $table->text('content');
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
        Schema::dropIfExists('cause_updates');
    }
}; 