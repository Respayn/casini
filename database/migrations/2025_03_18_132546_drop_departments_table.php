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
        Schema::dropIfExists('departments');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название департамента');
            $table->string('description')->comment('Описание департамента');

            $table->index('name');
        });
    }
};
