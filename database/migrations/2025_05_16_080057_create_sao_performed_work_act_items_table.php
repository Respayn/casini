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
        Schema::create('sao_performed_work_act_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_act_id')
                ->constrained('sao_performed_work_acts')
                ->onDelete('cascade')
                ->comment('Связанный акт');

            $table->unsignedSmallInteger('number')->comment('Порядковый номер');
            $table->string('name', 1000)->comment('Наименование работы');
            $table->decimal('quantity', 12, 3)->comment('Количество');
            $table->string('unit', 20)->comment('Единица измерения');
            $table->decimal('price', 15, 2)->comment('Цена за единицу');

            $table->timestamps();

            $table->unique(['work_act_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sao_performed_work_act_items');
    }
};
