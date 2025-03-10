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
        Schema::create('project_bonus_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('ID проекта');
            $table->boolean('bonuses_enabled')->default(false)->comment('Предусмотрены ли бонусы в проекте');
            $table->boolean('calculate_in_percentage')->default(false)->comment('Вычислять в процентах');
            $table->decimal('client_payment', 15)->nullable()->comment('Чек клиента');
            $table->unsignedTinyInteger('start_month')->default(1)->comment('С какого месяца начать считать');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_bonus_conditions');
    }
};
