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
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('rate_values', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('rate_id');
            $table->integer('value');
            $table->date('start_date');
            $table->date('end_date')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rates');
        Schema::dropIfExists('rate_values');
    }
};
