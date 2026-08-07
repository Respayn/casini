<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('callibri_daily_lead_counts', function (Blueprint $table) {
            $table->comment('Дневное число лидов Callibri по клиенто-проекту (после фильтров интеграции)');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date')
                ->comment('Локальный день агентства, за который сняты лиды');

            $table->unsignedInteger('leads_count')
                ->default(0);

            $table->timestamps();

            $table->unique(['project_id', 'date']);
            $table->index(['project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('callibri_daily_lead_counts');
    }
};
