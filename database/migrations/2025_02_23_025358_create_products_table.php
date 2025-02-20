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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->comment('Название продукта');
            $table->boolean('is_restricted')->comment('У обычных пользователей есть доступ к продукту')->default(false);
            $table->text('notification')->comment('Уведомления о действиях в продукте')->nullable(true);
            $table->string('code')->unique()->comment('Уникальный символьный код');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
