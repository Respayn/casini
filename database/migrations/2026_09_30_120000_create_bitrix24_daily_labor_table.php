<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitrix24_daily_labor', function (Blueprint $table) {
            $table->comment('Дневные часы из задач Битрикс24 по клиенто-проекту и сотруднику Касини');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date')
                ->comment('Локальный день агентства, в который записано затраченное время');

            $table->date('report_month')
                ->comment('Первое число месяца отчёта: из названия задачи, иначе месяц дня записи');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('role', 32)
                ->comment('Колонка Каналов: seo-assistant, seo-specialist, analyst, ork-manager');

            $table->unsignedInteger('seconds')
                ->default(0);

            $table->timestamps();

            $table->unique(['project_id', 'date', 'report_month', 'user_id']);
            $table->index(['project_id', 'report_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitrix24_daily_labor');
    }
};
