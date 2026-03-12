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
        Schema::create('completed_works', function (Blueprint $table) {
            $table->comment('Выполненные работы для отчётов');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Проект, к которому относится работа');

            $table->string('title')
                ->comment('Название выполненной работы');

            $table->date('completed_at')
                ->comment('Дата выполнения работы');

            $table->timestamps();

            $table->index(['project_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_works');
    }
};
