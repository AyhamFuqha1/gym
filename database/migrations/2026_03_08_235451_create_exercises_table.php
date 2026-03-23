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
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_exercise_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('difficulty_level');
            $table->string('video_url')->nullable();
            $table->string('instructions')->nullable();
            $table->string('common_mistakes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
