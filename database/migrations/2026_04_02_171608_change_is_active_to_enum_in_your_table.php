<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
    
        Schema::table('program_versions', function (Blueprint $table) {
            $table->enum('is_active', ['accepted', 'cancel', 'pending'])
                  ->default('pending')
                  ->change();
        });
    }

    public function down()
    {
     
        Schema::table('program_versions', function (Blueprint $table) {
            $table->tinyInteger('is_active')
                  ->default(1)
                  ->change();
        });

   
        DB::table('program_versions')
            ->where('is_active', 'accepted')
            ->update(['is_active' => 1]);

        DB::table('program_versions')
            ->where('is_active', 'cancel')
            ->update(['is_active' => 0]);

        DB::table('program_versions')
            ->where('is_active', 'pending')
            ->update(['is_active' => 0]);
    }
};
