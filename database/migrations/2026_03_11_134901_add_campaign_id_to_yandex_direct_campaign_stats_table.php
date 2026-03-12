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
        Schema::table('yandex_direct_campaign_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')
                ->nullable()
                ->after('campaign_name')
                ->comment('ID рекламной кампании в Яндекс.Директ');

            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yandex_direct_campaign_stats', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
    }
};
