<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_food_items', function (Blueprint $table) {
            $table->id();
            
       
            $table->foreignId('nutrition_version_id')
                  ->constrained('nutrition_versions')
                  ->onDelete('cascade');


            $table->foreignId('nutrition_id')
                  ->constrained('nutrition')
                  ->onDelete('cascade');


            $table->string('quantity')->nullable(); 

            
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])
                  ->default('breakfast');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_food_items');
    }
};
