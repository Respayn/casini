<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('column_grouping', 16)
                ->default('months')
                ->comment('Группировка колонок дат: months, quarters, years');
            $table->string('date_sort', 16)
                ->default('newest_first')
                ->comment('Порядок дат: newest_first, oldest_first');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['column_grouping', 'date_sort']);
        });
    }
};
