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
        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('cause_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('processing_fee', 10, 2)->default(0);
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('cover_fees')->default(false);
            $table->string('currency_code', 3)->default('USD');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->boolean('is_gift')->default(false);
            $table->text('gift_message')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
                  
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
        Schema::dropIfExists('donations');
    }
};
