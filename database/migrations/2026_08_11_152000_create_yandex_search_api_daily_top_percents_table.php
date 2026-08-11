<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yandex_search_api_daily_top_percents', function (Blueprint $table) {
            $table->comment('Дневной % фраз в ТОП-10 по Yandex Search API');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date')
                ->comment('День снимка (attribution date)');

            $table->decimal('top_10_percent', 5, 1)
                ->default(0);

            $table->unsignedInteger('phrases_count')
                ->default(0);

            $table->timestamps();

            $table->unique(['project_id', 'date']);
            $table->index(['project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yandex_search_api_daily_top_percents');
    }
};
