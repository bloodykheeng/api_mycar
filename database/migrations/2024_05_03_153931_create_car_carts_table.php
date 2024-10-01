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
        Schema::create('car_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_id');
            $table->integer('selected_quantity');
            $table->decimal('price', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('created_by', 'fk_car_cart_created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('car_id', 'fk_car_cart_car_id')
                ->references('id')
                ->on('cars') // Assuming 'cars' is the table name where 'car_id' comes from
                ->onDelete('cascade');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_carts');
    }
};
