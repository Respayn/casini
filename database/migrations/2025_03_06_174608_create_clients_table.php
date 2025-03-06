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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Имя клиента');
            $table->string('inn')->nullable()->comment('ИНН');
            $table->decimal('initial_balance', 10, 2)->default(0)->comment('Начальный баланс');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->comment('ID менеджера');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
