<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE suggestions MODIFY status ENUM('new', 'pending', 'reviewed', 'accepted', 'implemented') NOT NULL DEFAULT 'pending'");
        }

        DB::table('suggestions')->where('status', 'new')->update(['status' => 'pending']);
        DB::table('suggestions')->where('status', 'accepted')->update(['status' => 'implemented']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE suggestions MODIFY status ENUM('pending', 'reviewed', 'implemented') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE suggestions MODIFY status ENUM('new', 'pending', 'reviewed', 'accepted', 'implemented') NOT NULL DEFAULT 'new'");
        }

        DB::table('suggestions')->where('status', 'pending')->update(['status' => 'new']);
        DB::table('suggestions')->where('status', 'implemented')->update(['status' => 'accepted']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE suggestions MODIFY status ENUM('new', 'reviewed', 'accepted') NOT NULL DEFAULT 'new'");
        }
    }
};
