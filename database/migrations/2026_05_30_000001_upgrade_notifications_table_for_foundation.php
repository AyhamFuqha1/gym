<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type')->nullable()->index();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('entity_type')->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->json('data')->nullable();
                $table->string('priority')->default('normal');
                $table->json('channels')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('dedupe_key')->nullable()->unique();
                $table->timestamps();
            });

            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'recipient_user_id')) {
                $table->foreignId('recipient_user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('notifications', 'actor_user_id')) {
                $table->foreignId('actor_user_id')
                    ->nullable()
                    ->after('recipient_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->nullable()->after('actor_user_id')->index();
            }

            if (!Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->after('type');
            }

            if (!Schema::hasColumn('notifications', 'body')) {
                $table->text('body')->nullable()->after('title');
            }

            if (!Schema::hasColumn('notifications', 'entity_type')) {
                $table->string('entity_type')->nullable()->after('body')->index();
            }

            if (!Schema::hasColumn('notifications', 'entity_id')) {
                $table->unsignedBigInteger('entity_id')->nullable()->after('entity_type')->index();
            }

            if (!Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('entity_id');
            }

            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->string('priority')->default('normal')->after('data');
            }

            if (!Schema::hasColumn('notifications', 'channels')) {
                $table->json('channels')->nullable()->after('priority');
            }

            if (!Schema::hasColumn('notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('channels');
            }

            if (!Schema::hasColumn('notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('read_at');
            }

            if (!Schema::hasColumn('notifications', 'dedupe_key')) {
                $table->string('dedupe_key')->nullable()->after('sent_at')->unique();
            }

            if (!Schema::hasColumn('notifications', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (
            Schema::hasColumn('notifications', 'user_id') &&
            Schema::hasColumn('notifications', 'recipient_user_id')
        ) {
            DB::table('notifications')
                ->whereNull('recipient_user_id')
                ->update(['recipient_user_id' => DB::raw('user_id')]);
        }

        if (
            Schema::hasColumn('notifications', 'message') &&
            Schema::hasColumn('notifications', 'body')
        ) {
            DB::table('notifications')
                ->whereNull('body')
                ->update(['body' => DB::raw('message')]);
        }

        if (
            Schema::hasColumn('notifications', 'is_read') &&
            Schema::hasColumn('notifications', 'read_at')
        ) {
            DB::table('notifications')
                ->where('is_read', true)
                ->whereNull('read_at')
                ->update(['read_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'actor_user_id')) {
                $table->dropConstrainedForeignId('actor_user_id');
            }

            if (Schema::hasColumn('notifications', 'recipient_user_id')) {
                $table->dropConstrainedForeignId('recipient_user_id');
            }

            foreach ([
                'type',
                'body',
                'entity_type',
                'entity_id',
                'data',
                'priority',
                'channels',
                'read_at',
                'sent_at',
                'dedupe_key',
                'updated_at',
            ] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
