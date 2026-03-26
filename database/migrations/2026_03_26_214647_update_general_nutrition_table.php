<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('general_nutrition', function (Blueprint $table) {

            $table->string('icon')->nullable()->after('category_name');

            $table->text('description')->nullable()->after('icon');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('general_nutrition', function (Blueprint $table) {

            $table->dropColumn(['icon', 'description']);

            $table->dropTimestamps();
        });
    }
};
