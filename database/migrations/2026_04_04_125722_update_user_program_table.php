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
        Schema::table('user_programs', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        schema::table('user_programs', function (Blueprint $table) {
            $table->enum('status', ['active', 'cancelled'])->default('active')->after('program_id')->change();
        });
    }
};
