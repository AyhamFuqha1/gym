<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('modification_requests', function (Blueprint $table) {
            $table->id();

          
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('program_version_id')
                  ->constrained()
                  ->cascadeOnDelete();

           
            $table->json('changes_summary')->nullable();
            $table->json('modified_plan')->nullable();
            $table->json('recommendations')->nullable();

            $table->json('user_feedback')->nullable();

    
            $table->string('source')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

        
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modification_requests');
    } 
};
