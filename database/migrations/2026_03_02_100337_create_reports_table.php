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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('project_id')->constrained();
            $table->date('period_start')->nullable(false)->comment('Начало периода отчета');
            $table->date('period_end')->nullable(false)->comment('Конец периода отчета');
            $table->foreignId('specialist_id')->constrained('users');
            $table->foreignId('manager_id')->constrained('users');
            $table->enum('format', ['docx', 'pdf'])->comment('Формат отчета');
            $table->boolean('is_ready')->default(false)->comment('Готовность отчета');
            $table->boolean('is_accepted')->default(false)->comment('Принят ли отчет менеджером');
            $table->boolean('is_sent')->default(false)->comment('Отправлен ли отчет клиенту');
            $table->foreignId('created_by')->constrained('users');
            $table->string('path')->nullable(false)->comment('Путь к файлу отчета');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
