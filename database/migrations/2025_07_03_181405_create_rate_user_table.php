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
        Schema::create('rate_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ID пользователя из таблицы users');
            $table->unsignedBigInteger('rate_id')->comment('ID ставки из таблицы rates');

            $table->timestamps();

            $table->index('user_id');
            $table->index('rate_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rate_id')->references('id')->on('rates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_user');
    }
};
