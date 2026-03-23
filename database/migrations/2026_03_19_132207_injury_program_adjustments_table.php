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
        Schema::create('injury_program_adjustments', function (Blueprint $table) {
            $table->id();


            $table->foreignId('injury_id')
                ->constrained('user_injuries')
                ->cascadeOnDelete();

            $table->foreignId('program_version_id')
                ->constrained('program_versions')
                ->cascadeOnDelete();


            $table->foreignId('old_exercise_id')
                ->constrained('exercises')
                ->cascadeOnDelete();

            $table->foreignId('new_exercise_id')
                ->nullable()
                ->constrained('exercises')
                ->nullOnDelete();


            $table->enum('action', ['replace', 'remove', 'modify']);


            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();


            $table->text('notes')->nullable();


            $table->enum('applied_by', ['ai', 'coach', 'system'])
                ->default('ai');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('injury_program_adjustments');
    }
};
