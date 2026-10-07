<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistics_project_monthly_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('kind', 32)->comment('not_configured|fill_check|amount');
            $table->decimal('value', 15, 2)->nullable()->comment('Сумма бонуса/гарантии в ₽ для kind=amount');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['project_id', 'year', 'month'], 'stats_proj_month_bonus_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistics_project_monthly_bonuses');
    }
};
