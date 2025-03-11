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
        Schema::create('project_field_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete()->comment('ID проекта');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кем изменено');
            $table->timestamp('changed_at')->useCurrent()->comment('Когда изменено');
            $table->string('field')->comment('Ключ поля');
            $table->text('old_value')->nullable()->comment('Старое значение');
            $table->text('new_value')->nullable()->comment('Новое значение');

            // Индексы
            $table->index('project_id');
            $table->index('changed_by');
            $table->index('field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_field_histories');
    }
};
