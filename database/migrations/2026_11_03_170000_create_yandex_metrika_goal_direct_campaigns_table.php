<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yandex_metrika_goal_direct_campaigns', function (Blueprint $table) {
            $table->comment('Достижения целей «Директ, сводка» в разрезе кампаний');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится статистика');

            $table->string('goal_name')
                ->comment('Название цели');

            $table->unsignedBigInteger('campaign_id')
                ->comment('ID кампании Яндекс.Директ');

            $table->string('campaign_name')
                ->default('')
                ->comment('Название кампании из Метрики');

            $table->date('month')
                ->comment('Месяц, за который собрана статистика');

            $table->unsignedInteger('conversions')
                ->default(0)
                ->comment('Количество достижений цели');

            $table->timestamps();

            $table->unique(
                ['project_id', 'goal_name', 'campaign_id', 'month'],
                'ym_goal_direct_campaign_unique'
            );
            $table->index(['project_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yandex_metrika_goal_direct_campaigns');
    }
};
