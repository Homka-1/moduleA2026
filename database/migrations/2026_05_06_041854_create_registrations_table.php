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
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreign('event_id')->references('id')->on('events');
            $table->unsignedBigInteger('event_id');
            $table->foreign('participant_id')->references('id')->on('participants');
            $table->unsignedBigInteger('participant_id');
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED'])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
