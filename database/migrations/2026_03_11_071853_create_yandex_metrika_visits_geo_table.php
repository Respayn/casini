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
        Schema::create('yandex_metrika_visits_geo', function (Blueprint $table) {
            $table->comment('Статистика визитов по географии из Яндекс.Метрики');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится статистика');

            $table->date('month')
                ->comment('Месяц, за который собрана статистика');

            $table->string('city')
                ->comment('Город');

            $table->unsignedInteger('visits')
                ->default(0)
                ->comment('Количество визитов');

            $table->unsignedInteger('visitors')
                ->default(0)
                ->comment('Количество посетителей');

            $table->unsignedInteger('goal_reaches')
                ->default(0)
                ->comment('Количество достижений целей');

            $table->timestamps();

            $table->unique(['project_id', 'month', 'city'], 'project_month_city_unique');
            $table->index(['project_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_metrika_visits_geo');
    }
};
