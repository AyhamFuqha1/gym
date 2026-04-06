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
            $table->decimal('daily_protein', 8, 2)->default(0)->after('daily_calories');
            $table->decimal('daily_carbs', 8, 2)->default(0)->after('daily_protein');
            $table->decimal('daily_fat', 8, 2)->default(0)->after('daily_carbs');
            $table->text('reason')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrition_versions', function (Blueprint $table) {
            $table->dropColumn(['daily_protein', 'daily_carbs', 'daily_fat', 'reason']);
        });
    }
};
