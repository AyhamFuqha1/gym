<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
      
        Schema::rename('nutrition', 'foods');

        Schema::table('foods', function (Blueprint $table) {

         
            $table->decimal('calories', 8, 2)->change();
            $table->decimal('protein', 5, 2)->change();
            $table->decimal('carbs', 5, 2)->change();
            $table->decimal('fat', 5, 2)->change();

   
            $table->string('serving_size')->nullable()->after('fat');
            $table->string('image')->nullable()->after('serving_size');

         
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {

        
            $table->dropColumn(['serving_size', 'image']);

           
            $table->integer('calories')->change();
            $table->integer('protein')->change();
            $table->integer('carbs')->change();
            $table->integer('fat')->change();

   
            $table->dropTimestamps();
        });


        Schema::rename('foods', 'nutrition');
    }
};
