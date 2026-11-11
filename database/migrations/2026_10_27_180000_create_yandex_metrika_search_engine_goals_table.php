<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yandex_metrika_search_engine_goals', function (Blueprint $table) {
            $table->comment('Цели из отчёта Поисковые системы Яндекс Метрики по поисковику');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится статистика');

            $table->string('search_engine')
                ->comment('Поисковая система');

            $table->unsignedInteger('goal_id')
                ->comment('Номер цели в Яндекс Метрике');

            $table->string('goal_name')
                ->comment('Название цели на момент съёма');

            $table->date('month')
                ->comment('Месяц, за который собрана статистика');

            $table->unsignedInteger('conversions')
                ->default(0)
                ->comment('Число достижений цели');

            $table->timestamps();

            $table->unique(
                ['project_id', 'search_engine', 'goal_id', 'month'],
                'project_engine_goal_month_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yandex_metrika_search_engine_goals');
    }
};
