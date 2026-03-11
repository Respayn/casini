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
        Schema::create('yandex_direct_campaign_stats', function (Blueprint $table) {
            $table->comment('Статистика рекламных кампаний Яндекс.Директ');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится статистика');

            $table->string('campaign_name')
                ->comment('Название рекламной кампании');

            $table->unsignedBigInteger('impressions')
                ->default(0)
                ->comment('Количество показов объявления');

            $table->unsignedInteger('clicks')
                ->default(0)
                ->comment('Количество кликов по объявлению');

            $table->decimal('cost_with_vat', 12, 2)
                ->default(0)
                ->comment('Расход с учётом НДС');

            $table->decimal('cost_without_vat', 12, 2)
                ->default(0)
                ->comment('Расход без НДС');

            $table->unsignedInteger('conversions')
                ->default(0)
                ->comment('Количество достигнутых конверсий');

            $table->string('goal_name')
                ->nullable()
                ->comment('Название цели конверсии');

            $table->date('date')
                ->comment('Дата, за которую собрана статистика');

            $table->timestamps();

            $table->index(['project_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_direct_campaign_stats');
    }
};
