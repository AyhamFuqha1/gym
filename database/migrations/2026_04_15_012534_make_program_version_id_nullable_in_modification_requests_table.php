<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modification_requests', function (Blueprint $table) {
            $table->foreignId('program_version_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('modification_requests', function (Blueprint $table) {
            $table->foreignId('program_version_id')->nullable(false)->change();
        });
    }
};