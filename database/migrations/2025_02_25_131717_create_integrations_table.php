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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->comment('Название интеграции');
            $table->string('category')->comment('Категория');
            $table->text('notification')->nullable(true)->default(null)->comment('Пользовательские уведомления');
            $table->string('code')->unique()->comment('Уникальный символьный код');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
