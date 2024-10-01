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
        Schema::create('car_inspection_report_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('car_inspection_reports_id');
            $table->unsignedBigInteger('car_inspection_field_categories_id');
            $table->timestamps();

            // Tracking who created and updated the fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('car_inspection_reports_id', 'fk_car_inspection_reports')
                ->references('id')->on('car_inspection_reports')->onDelete('CASCADE');
            $table->foreign('car_inspection_field_categories_id', 'fk_car_inspection_field_categories')
                ->references('id')->on('car_inspection_field_categories')->onDelete('CASCADE');
            $table->foreign('created_by', 'fk_created_by')
                ->references('id')->on('users')->onDelete('SET NULL');
            $table->foreign('updated_by', 'fk_updated_by')
                ->references('id')->on('users')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_inspection_report_categories');
    }
};