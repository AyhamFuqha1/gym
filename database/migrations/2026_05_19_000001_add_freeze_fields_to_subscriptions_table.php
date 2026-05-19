<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled', 'frozen') NOT NULL DEFAULT 'active'");

        if (!Schema::hasColumn('subscriptions', 'frozen_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('frozen_at')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('subscriptions', 'resumed_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->timestamp('resumed_at')->nullable()->after('frozen_at');
            });
        }

        if (!Schema::hasColumn('subscriptions', 'frozen_remaining_days')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->integer('frozen_remaining_days')->nullable()->after('resumed_at');
            });
        }

        DB::statement("
            UPDATE subscriptions
            SET frozen_remaining_days = GREATEST(DATEDIFF(end_date, CURDATE()), 0),
                frozen_at = COALESCE(frozen_at, updated_at, NOW())
            WHERE status = 'frozen'
              AND frozen_remaining_days IS NULL
        ");
    }

    public function down(): void
    {
        DB::table('subscriptions')
            ->where('status', 'frozen')
            ->update(['status' => 'cancelled']);

        if (Schema::hasColumn('subscriptions', 'frozen_remaining_days')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('frozen_remaining_days');
            });
        }

        if (Schema::hasColumn('subscriptions', 'resumed_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('resumed_at');
            });
        }

        if (Schema::hasColumn('subscriptions', 'frozen_at')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropColumn('frozen_at');
            });
        }

        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
    }
};
