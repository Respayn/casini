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
        Schema::create('user_column_preferences', function (Blueprint $table) {
            $table->id();
            $table->string('table_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('settings');
            $table->timestamps();

            $table->unique(['table_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_column_preferences');
    }
};
