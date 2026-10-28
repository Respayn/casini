<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_works', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок пункта в чек-листе');
            $table->unsignedTinyInteger('depth')->default(0)->comment('Уровень вложенности пункта чек-листа');
        });
    }

    public function down(): void
    {
        Schema::table('completed_works', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'depth']);
        });
    }
};
