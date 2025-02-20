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
        Schema::create('promotion_topics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('category')->comment('Категория');
            $table->string('topic')->comment('Тематика');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_topics');
    }
};
