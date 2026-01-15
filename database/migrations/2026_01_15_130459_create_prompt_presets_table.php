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
        Schema::create('prompt_presets', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20); // dev, study, sales
            $table->string('title', 100); // 表示名（例: 「提案書を作成」）
            $table->text('prompt'); // プロンプト本文
            $table->string('icon', 10)->nullable(); // アイコン（絵文字）
            $table->integer('order')->default(0); // 表示順
            $table->boolean('is_active')->default(true); // 有効/無効
            $table->timestamps();
            
            $table->index('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_presets');
    }
};
