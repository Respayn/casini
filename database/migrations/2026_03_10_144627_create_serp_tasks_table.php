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
        Schema::create('serp_tasks', function (Blueprint $table) {
            $table->comment('Атомарные единицы мониторинга: фраза + поисковик + регион + проект');

            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete()
                ->comment('Проект, для которого выполняется проверка');

            $table->foreignId('serp_keyword_id')
                ->constrained('serp_keywords')
                ->cascadeOnDelete()
                ->comment('Ключевая фраза для снятия позиции');

            $table->foreignId('search_engine_id')
                ->constrained('search_engines')
                ->comment('Поисковая система');

            $table->foreignId('serp_region_id')
                ->constrained('serp_regions')
                ->comment('Регион поиска');

            $table->boolean('is_active')
                ->default(true);

            $table->string('check_frequency')
                ->default('daily')
                ->comment('Периодичность проверки');

            $table->timestamp('last_check_at')->nullable()
                ->comment('Время последней успешной проверки. NULL - ещё не проверялась');

            $table->unique(
                ['project_id', 'serp_keyword_id', 'search_engine_id', 'serp_region_id'],
                'uq_serp_task'
            );

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_tasks');
    }
};
