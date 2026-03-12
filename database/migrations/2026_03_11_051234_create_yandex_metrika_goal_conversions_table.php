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
        Schema::create('yandex_metrika_goal_conversions', function (Blueprint $table) {
            $table->comment('Достижения целей из отчёта "Конверсии" Яндекс.Метрики');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится статистика');

            $table->string('goal_name')
                ->comment('Название цели');

            $table->date('month')
                ->comment('Месяц, за который собрана статистика');

            $table->unsignedInteger('conversions')
                ->default(0)
                ->comment('Количество достижений цели');

            $table->timestamps();

            $table->unique(['project_id', 'goal_name', 'month'], 'project_goal_month_unique');
            $table->index(['project_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_metrika_goal_conversions');
    }
};
