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
        Schema::create('nutrition', function (Blueprint $table) {
            $table->id();
            
          
            $table->foreignId('general_nutrition_id')
                  ->constrained('general_nutrition', 'id', 'nutrition_gen_foreign')
                  ->cascadeOnDelete();

            $table->string('name');
            $table->integer('calories');
            $table->float('protein');
            $table->float('carbs');
            $table->float('fat');
            
           
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nutrition');
    }
};