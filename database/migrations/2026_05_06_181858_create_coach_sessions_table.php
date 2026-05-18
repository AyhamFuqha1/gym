<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('coach_sessions')) {
            Schema::create('coach_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
                $table->tinyInteger('day_of_week')->nullable();
                $table->date('session_date')->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->integer('capacity');
                $table->integer('booked_count')->default(0);
                $table->enum('status', ['available', 'full', 'cancelled'])->default('available');
                $table->boolean('is_recurring')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_sessions');
    }
};
