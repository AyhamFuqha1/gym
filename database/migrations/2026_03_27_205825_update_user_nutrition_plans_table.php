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
        Schema::table('user_nutrition_plans', function (Blueprint $table) {
            $table->dropForeign(['nutrition_version_id']);
            $table->dropColumn('nutrition_version_id');
            $table->string('name')->after('id');
            $table->string('goal_type')->nullable();
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
