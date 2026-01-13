<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // user_id カラムを追加
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
        
        // 既存の name UNIQUE 制約を削除（もしあれば）
        try {
            Schema::table('tags', function (Blueprint $table) {
                $table->dropUnique(['name']);
            });
        } catch (\Exception $e) {
            // UNIQUE制約がなければスキップ
        }
        
        // user_id + name の複合ユニーク制約を追加
        Schema::table('tags', function (Blueprint $table) {
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
