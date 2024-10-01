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
        Schema::create('inspection_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->string('field_type');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('car_inspection_field_categories_id')->nullable();
            $table->timestamps();

            // Assuming you want to track who created and updated the fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('car_inspection_field_categories_id')->references('id')->on('car_inspection_field_categories')->onDelete('CASCADE');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('SET NULL');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_fields');
    }
};