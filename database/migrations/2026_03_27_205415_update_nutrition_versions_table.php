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
        Schema::table('nutrition_versions', function (Blueprint $table) {

            $table->dropColumn('goal_type');

            $table->foreignId('user_nutrition_plan_id')
                ->after('id')
                ->constrained('user_nutrition_plans')
                ->cascadeOnDelete();


            $table->boolean('is_active')->default(true);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
