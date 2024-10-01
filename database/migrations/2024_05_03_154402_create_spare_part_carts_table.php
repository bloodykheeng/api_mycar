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
        Schema::create('spare_part_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spare_part_id');
            $table->integer('selected_quantity');
            $table->decimal('price', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('spare_part_id', 'fk_spare_part_cart_spare_part_id')
                ->references('id')
                ->on('spare_parts') // Adjust this if your table name is different
                ->onDelete('cascade');

            $table->foreign('created_by', 'fk_spare_part_cart_created_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('spare_part_carts');
    }
};
