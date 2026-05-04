<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nutrition') && !Schema::hasTable('foods')) {
            Schema::rename('nutrition', 'foods');
        }

        Schema::table('foods', function (Blueprint $table) {
            $table->decimal('calories', 8, 2)->change();
            $table->decimal('protein', 5, 2)->change();
            $table->decimal('carbs', 5, 2)->change();
            $table->decimal('fat', 5, 2)->change();

            if (!Schema::hasColumn('foods', 'serving_size')) {
                $table->string('serving_size')->nullable()->after('fat');
            }

            if (!Schema::hasColumn('foods', 'image')) {
                $table->string('image')->nullable()->after('serving_size');
            }

            if (!Schema::hasColumn('foods', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('foods', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('foods')) {
            Schema::table('foods', function (Blueprint $table) {
                if (Schema::hasColumn('foods', 'serving_size')) {
                    $table->dropColumn('serving_size');
                }

                if (Schema::hasColumn('foods', 'image')) {
                    $table->dropColumn('image');
                }

                if (Schema::hasColumn('foods', 'created_at')) {
                    $table->dropColumn('created_at');
                }

                if (Schema::hasColumn('foods', 'updated_at')) {
                    $table->dropColumn('updated_at');
                }

                $table->integer('calories')->change();
                $table->integer('protein')->change();
                $table->integer('carbs')->change();
                $table->integer('fat')->change();
            });
        }

        if (Schema::hasTable('foods') && !Schema::hasTable('nutrition')) {
            Schema::rename('foods', 'nutrition');
        }
    }
};