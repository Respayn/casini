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
        Schema::create('project_plan_values', function (Blueprint $table) {
            $table->comment('Плановые значения целевых показателей проектов');
            $table->id();
            $table->foreignId('project_id')->comment('Идентификатор проекта')->constrained('projects')->cascadeOnDelete();
            $table->string('parameter_code')->comment('Код для идентификации параметра');
            $table->float('value')->nullable(true)->comment('Значение параметра');
            $table->date('year_month_date')->comment('Дата планового значения');
            $table->timestamps();

            $table->unique(['project_id', 'parameter_code', 'year_month_date'], 'project_plan_values_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_plan_values');
    }
};
