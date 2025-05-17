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
        Schema::create('sao_performed_work_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Связанный проект');

            $table->string('number', 50)->unique()->comment('Уникальный номер акта');
            $table->date('creation_date')->comment('Дата составления акта');
            $table->decimal('price', 15, 2)->comment('Общая сумма акта');
            $table->string('customer_inn', 12)->nullable()->comment('ИНН заказчика');
            $table->string('contract_number', 100)->nullable()->comment('Номер договора');
            $table->string('customer_additional_number', 100)->nullable()->comment('Доп. номер договора');
            $table->timestamps();

            $table->softDeletes();

            $table->index(['number', 'creation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sao_performed_work_acts');
    }
};
