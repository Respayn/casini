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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->comment('Номер платежа');
            $table->string('source', 50)->comment('Источник платежа');
            $table->date('received_date')->comment('Дата получения');
            $table->string('external_id', 100)->nullable()->unique()->comment('Внешний ID из 1С');

            $table->foreignId('client_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Связанный клиент');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['number', 'received_date']);
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
