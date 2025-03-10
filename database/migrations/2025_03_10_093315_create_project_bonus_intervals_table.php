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
        Schema::create('project_bonus_intervals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_bonus_condition_id')->comment('ID связанного условия бонуса');
            $table->decimal('from_percentage', 5)->comment('Выполнение плана в %, от (включительно)');
            $table->decimal('to_percentage', 5)->comment('Выполнение плана в %, по (включительно)');
            $table->decimal('bonus_amount', 15)->nullable()->comment('Бонус в рублях (может быть отрицательным для гарантий)');
            $table->decimal('bonus_percentage', 5)->nullable()->comment('Бонус в % от чека клиента (может быть отрицательным для гарантий)');
            $table->timestamps();

            $table->foreign('project_bonus_condition_id', 'pbc_id_foreign')->references('id')->on('project_bonus_conditions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_bonus_intervals');
    }
};
