<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('session_id')->constrained('coach_sessions')->cascadeOnDelete();
                $table->enum('status', ['booked', 'cancelled'])->default('booked');
                $table->timestamps();

                $table->unique(['user_id', 'session_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
