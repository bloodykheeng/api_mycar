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
        Schema::create('spare_parts_transactions_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->unsignedBigInteger('spare_parts_id')->nullable();
            $table->unsignedBigInteger('spare_parts_transactions_id');
            $table->integer('quantity');
            $table->timestamps();

            // Tracking who created and updated the fields
            $table->unsignedBigInteger('created_by')->nullable();

            // Foreign key constraints
            $table->foreign('spare_parts_id', 'fk_sptp_spare_parts')->references('id')->on('spare_parts')->onDelete('SET NULL');
            $table->foreign('spare_parts_transactions_id', 'fk_sptp_spare_parts_txn_id')->references('id')->on('spare_parts_transactions')->onDelete('CASCADE');
            $table->foreign('created_by', 'fk_sptp_created_by')->references('id')->on('users')->onDelete('SET NULL');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_parts_transactions_products');
    }
};