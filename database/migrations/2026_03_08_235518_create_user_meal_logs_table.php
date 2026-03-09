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
        Schema::create('user_meal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_nutrition_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nutrition_id')->constrained()->cascadeOnDelete();
            $table->string('meal_type');
            $table->integer('quantity');
            $table->integer('calories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_meal_logs');
    }
};
