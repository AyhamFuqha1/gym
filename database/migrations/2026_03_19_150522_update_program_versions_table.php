<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_versions', function (Blueprint $table) {

            // 🔗 المستخدم
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // 🔗 البرنامج الأساسي (plan)
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('plans')
                ->nullOnDelete();

            // 🧠 سبب إنشاء النسخة
            $table->string('source_type')->nullable(); // injury, ai, goal, coach

            $table->unsignedBigInteger('source_id')->nullable();

            // ⚡ النسخة الحالية
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('program_versions', function (Blueprint $table) {

            $table->dropForeign(['user_id']);
            $table->dropForeign(['plan_id']);

            $table->dropColumn([
                'user_id',
                'plan_id',
                'source_type',
                'source_id',
                'is_active'
            ]);
        });
    }
};