<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_sync_runs', function (Blueprint $table) {
            $table->comment('Ночной (или ручной) прогон съёма интеграций за локальную дату агентства');

            $table->id();

            $table->date('local_date')
                ->comment('Календарная дата в timezone агентства, когда стартовал run (обычно «сегодня» в 00:01)');

            $table->string('timezone', 64);

            $table->date('target_date')
                ->comment('День, за который снимаем данные (вчера относительно local_date)');

            $table->string('status', 32)
                ->default('pending')
                ->comment('pending|running|completed|failed');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->timestamps();

            $table->unique(['local_date', 'timezone']);
        });

        Schema::create('integration_sync_items', function (Blueprint $table) {
            $table->comment('Элемент очереди съёма: проект + collector');

            $table->id();

            $table->foreignId('run_id')
                ->constrained('integration_sync_runs')
                ->cascadeOnDelete();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('collector', 64)
                ->comment('Ключ IntegrationSyncCollector');

            $table->string('status', 32)
                ->default('pending')
                ->comment('pending|processing|done|failed');

            $table->unsignedTinyInteger('attempts')
                ->default(0);

            $table->unsignedInteger('position')
                ->default(0)
                ->comment('Порядок в очереди; при requeue увеличивается');

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['run_id', 'status', 'position']);
            $table->index(['run_id', 'project_id', 'collector']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_sync_items');
        Schema::dropIfExists('integration_sync_runs');
    }
};
