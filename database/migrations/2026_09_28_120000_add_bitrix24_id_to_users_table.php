<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('bitrix24_id')->nullable()->unique()->after('megaplan_id')->comment('ID пользователя в Битрикс24');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['bitrix24_id']);
            $table->dropColumn('bitrix24_id');
        });
    }
};
