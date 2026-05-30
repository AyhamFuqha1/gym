<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('push_tokens')) {
            Schema::create('push_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->text('token');
                $table->string('provider')->default('expo');
                $table->string('platform')->nullable();
                $table->string('device_id')->nullable();
                $table->string('app_version')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        } else {
            Schema::table('push_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('push_tokens', 'provider')) {
                    $table->string('provider')->default('expo')->after('token');
                }

                if (!Schema::hasColumn('push_tokens', 'platform')) {
                    $table->string('platform')->nullable()->after('provider');
                }

                if (!Schema::hasColumn('push_tokens', 'device_id')) {
                    $table->string('device_id')->nullable()->after('platform');
                }

                if (!Schema::hasColumn('push_tokens', 'app_version')) {
                    $table->string('app_version')->nullable()->after('device_id');
                }

                if (!Schema::hasColumn('push_tokens', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('app_version');
                }

                if (!Schema::hasColumn('push_tokens', 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->after('is_active');
                }

                if (!Schema::hasColumn('push_tokens', 'revoked_at')) {
                    $table->timestamp('revoked_at')->nullable()->after('last_seen_at');
                }
            });
        }

        $this->ensureIndex('push_tokens', 'push_tokens_user_id_index', 'user_id');
        $this->ensureTokenIndex();
        $this->ensureTokenUniqueIndexIfSafe();
    }

    public function down(): void
    {
        if (!Schema::hasTable('push_tokens')) {
            return;
        }

        $this->dropIndexIfExists('push_tokens', 'push_tokens_token_unique', true);
        $this->dropIndexIfExists('push_tokens', 'push_tokens_token_index');

        Schema::table('push_tokens', function (Blueprint $table) {
            foreach ([
                'provider',
                'platform',
                'device_id',
                'app_version',
                'is_active',
                'last_seen_at',
                'revoked_at',
            ] as $column) {
                if (Schema::hasColumn('push_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function ensureIndex(string $tableName, string $indexName, string $column): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        } catch (Throwable $e) {
            Log::warning("Could not create {$indexName}.", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ensureTokenIndex(): void
    {
        if ($this->indexExists('push_tokens', 'push_tokens_token_index')) {
            return;
        }

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE push_tokens ADD INDEX push_tokens_token_index (token(191))');
                return;
            }

            Schema::table('push_tokens', function (Blueprint $table) {
                $table->index('token', 'push_tokens_token_index');
            });
        } catch (Throwable $e) {
            Log::warning('Could not create push_tokens token index.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ensureTokenUniqueIndexIfSafe(): void
    {
        if ($this->indexExists('push_tokens', 'push_tokens_token_unique')) {
            return;
        }

        $duplicate = DB::table('push_tokens')
            ->select('token')
            ->groupBy('token')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->exists();

        if ($duplicate) {
            Log::warning('Skipped unique push_tokens.token index because duplicate tokens already exist.');
            return;
        }

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE push_tokens ADD UNIQUE INDEX push_tokens_token_unique (token(191))');
                return;
            }

            Schema::table('push_tokens', function (Blueprint $table) {
                $table->unique('token', 'push_tokens_token_unique');
            });
        } catch (Throwable $e) {
            Log::warning('Could not create unique push_tokens token index.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dropIndexIfExists(string $tableName, string $indexName, bool $unique = false): void
    {
        if (!$this->indexExists($tableName, $indexName)) {
            return;
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName, $unique) {
                $unique ? $table->dropUnique($indexName) : $table->dropIndex($indexName);
            });
        } catch (Throwable $e) {
            Log::warning("Could not drop {$indexName}.", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return !empty(DB::select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                [$tableName, $indexName]
            ));
        }

        if (method_exists(Schema::class, 'getIndexes')) {
            return collect(Schema::getIndexes($tableName))
                ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
        }

        return false;
    }
};
