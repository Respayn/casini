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
        Schema::create('agency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('time_zone');
            $table->string('url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('logo_src')->nullable();
            $table->timestamps();
        });

        Schema::create('agency_admins', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->foreignId('agency_id')->constrained('agency_settings')->onDelete('cascade')->comment('Агентство');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Пользователь-администратор');
            $table->string('name')->comment('Имя администратора на момент добавления');
            $table->timestamps();

            $table->unique(['agency_id', 'user_id'], 'agency_user_unique');
            $table->index('agency_id', 'idx_agency_id');
            $table->index('user_id', 'idx_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_admins');
        Schema::dropIfExists('agency_settings');
    }
};
