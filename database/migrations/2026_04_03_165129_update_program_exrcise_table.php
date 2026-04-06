<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_exercises', function (Blueprint $table) {
            $table->dropColumn('order_in_day');
            $table->string('difficulty')->default("medium");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_exercises', function (Blueprint $table) {
            $table->dropColumn('difficulty');
            $table->integer('order_in_day')->default(1);
        });
    }
};
