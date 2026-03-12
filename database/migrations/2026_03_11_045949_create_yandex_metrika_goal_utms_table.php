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
        Schema::create('yandex_metrika_goal_utms', function (Blueprint $table) {
            $table->comment('Данные о достижении целей с UTM-метками из Яндекс.Метрики');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относятся данные');

            $table->string('goal_name')
                ->comment('Название цели');

            $table->date('achieved_date')
                ->comment('Дата достижения цели');

            $table->string('utm_source')
                ->nullable()
                ->comment('UTM Source');

            $table->string('utm_medium')
                ->nullable()
                ->comment('UTM Medium');

            $table->string('utm_campaign')
                ->nullable()
                ->comment('UTM Campaign');

            $table->string('utm_content')
                ->nullable()
                ->comment('UTM Content');

            $table->string('utm_term')
                ->nullable()
                ->comment('UTM Term');

            $table->timestamps();

            $table->index(['project_id', 'achieved_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_metrika_goal_utms');
    }
};
