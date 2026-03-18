<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('program_exercises', function (Blueprint $table) {
            $table->id();

            // FK
            $table->foreignId('program_version_id')
                ->constrained('program_versions')
                ->cascadeOnDelete();

            $table->foreignId('exercise_id')
                ->constrained('exercises')
                ->cascadeOnDelete();

            // تفاصيل التمرين داخل البرنامج
            $table->integer('sets')->default(3);
            $table->integer('reps')->default(10);
            $table->integer('rest_seconds')->nullable();

            // تنظيم الجدول
            $table->integer('day_number');      // اليوم (Day 1, Day 2...)
            $table->integer('order_in_day');    // ترتيب التمرين داخل اليوم

            $table->timestamps();

            // منع التكرار
            $table->unique(
                ['program_version_id', 'exercise_id', 'day_number', 'order_in_day'],
                'prog_ex_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_exercises');
    }
};
