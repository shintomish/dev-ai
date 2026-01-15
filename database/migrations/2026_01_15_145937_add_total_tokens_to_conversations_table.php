<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->integer('total_tokens')->default(0)->after('mode');
            $table->decimal('total_cost_usd', 10, 6)->default(0)->after('total_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['total_tokens', 'total_cost_usd']);
        });
    }
};
