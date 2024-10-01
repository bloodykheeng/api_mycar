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
        Schema::create('spare_parts_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone_number')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('tx_ref')->nullable();
            $table->string('flw_ref')->nullable();
            $table->string('currency', 3)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('charged_amount', 15, 2)->nullable();
            $table->string('charge_response_code')->nullable();
            $table->string('charge_response_message')->nullable();
            $table->datetime('gateway_created_at')->index()->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts_transactions');
    }
};
