<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yandex_direct_campaign_stats', function (Blueprint $table) {
            $table->unique(
                ['project_id', 'campaign_id', 'date'],
                'yd_campaign_stats_project_campaign_date_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('yandex_direct_campaign_stats', function (Blueprint $table) {
            $table->dropUnique('yd_campaign_stats_project_campaign_date_unique');
        });
    }
};
