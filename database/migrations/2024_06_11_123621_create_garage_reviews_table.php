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
        Schema::create('garage_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('garage_id');
            $table->text('comment');
            $table->integer('rating'); // Rating out of 5, for example
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('garage_id')->references('id')->on('garages')->onDelete('CASCADE');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('CASCADE');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garage_reviews');
    }
};