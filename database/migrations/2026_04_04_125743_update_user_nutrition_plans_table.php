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
        Schema::table('user_nutrition_plans', function (Blueprint $table) {
            $table->enum('active',['pending', 'active', 'cancelled'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_nutrition_plans', function (Blueprint $table) {
            $table->enum('active',['active', 'cancelled'])->default('active')->after('nutrition_plan_id')->change();
        });
    }
};
