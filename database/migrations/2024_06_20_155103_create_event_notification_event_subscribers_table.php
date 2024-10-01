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
        Schema::create('event_notification_event_subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_notification_id');
            $table->unsignedBigInteger('event_subscriber_id');

            $table->foreign('event_notification_id','fk_enes_event_notification_id')->references('id')->on('event_notifications')->onDelete('cascade');
            $table->foreign('event_subscriber_id','fk_enes_event_subscriber_id')->references('id')->on('event_subscribers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_notification_event_subscribers');
    }
};
