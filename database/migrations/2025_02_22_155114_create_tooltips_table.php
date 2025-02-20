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
        Schema::create('tooltips', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Уникальный идентфикатор тултипа');
            $table->string('path')->comment('Путь');
            $table->string('label')->comment('Поле рядом с тултипом');
            $table->text('content')->comment('Содержание тултипа');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tooltips');
    }
};
