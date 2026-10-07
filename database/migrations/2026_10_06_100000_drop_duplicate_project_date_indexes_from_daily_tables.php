<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Индекс project_id + date дублирует unique по тем же колонкам.
     * На уже накатанных базах он есть, на свежих его не создают.
     */
    private const TABLES = [
        'yandex_direct_daily_spendings',
        'callibri_daily_lead_counts',
        'yandex_search_api_daily_top_percents',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            $index = $tableName.'_project_id_date_index';

            if (! Schema::hasIndex($tableName, $index)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }

    public function down(): void
    {
        //
    }
};
