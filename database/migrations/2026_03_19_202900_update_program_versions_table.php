<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('program_versions', function (Blueprint $table) {

            $table->dropForeign(['user_id']);
            $table->dropForeign(['plan_id']);

            $table->dropColumn(['user_id', 'plan_id']);                                         
           $table->foreignId('user_program_id')
                ->constrained('user_programs')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('program_versions', function (Blueprint $table) {

            $table->dropForeign(['user_programe_id']);
            $table->dropColumn('user_programe_id'); 

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
         
        });
    }
};
