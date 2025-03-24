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
        Schema::create('integration_project', function (Blueprint $table) {
            $table->comment('Связь проектов с интеграциями');

            $table->id();
            $table->foreignId('project_id')->comment('Связь с проектом')->constrained();
            $table->foreignId('integration_id')->comment('Связь с интеграцией')->constrained();
            $table->boolean('is_enabled')->default(false)->comment('Включена ли интеграция');
            $table->json('settings')->comment('Настройки')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_project');
    }
};
