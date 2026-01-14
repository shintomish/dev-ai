<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // mode カラムを varchar(20) に変更
            $table->string('mode', 20)->default('dev')->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // 元に戻す（必要に応じて）
            $table->enum('mode', ['dev', 'study'])->default('dev')->change();
        });
    }
};
