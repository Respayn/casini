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
        Schema::create('project_plan_approvals', function (Blueprint $table) {
            $table->comment('Значения согласований проектов');
            $table->id();
            $table->timestamps();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('period')->comment('Периодичность согласования');
            $table->integer('year')->comment('Год согласования');
            $table->integer('period_number')->comment('Номер периода согласования');
            $table->boolean('approved')->comment('Согласован');

            $table->unique(['project_id', 'period', 'year', 'period_number'], 'project_plan_approvals_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_plan_approvals');
    }
};
