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
        Schema::create('serp_positions', function (Blueprint $table) {
            $table->comment('Результат каждой проверки позиции');

            $table->id();

            $table->foreignId('serp_task_id')
                ->constrained('serp_tasks')
                ->cascadeOnDelete();

            $table->date('check_date')
                ->comment('Дата снятия позиции');

            $table->smallInteger('position')
                ->nullable()
                ->comment('Позиция. NULL = не найден');

            $table->string('url')
                ->nullable()
                ->comment('URL страницы сайта в выдаче');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_positions');
    }
};
