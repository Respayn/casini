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
        Schema::create('product_notifications', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('product_id')->nullable(true);
            $table->string('category');
            $table->text('content');
            $table->string('code')->unique()->comment('Уникальный символьный код');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_notifications');
    }
};
