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
        Schema::create('payment_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('order')->comment('Порядковый номер операции');
            $table->string('invoice_number', 100)->nullable()->comment('Номер счета');
            $table->date('invoice_date')->nullable()->comment('Дата счета');
            $table->decimal('bank_received_amount', 15, 2)->comment('Сумма в банке');
            $table->decimal('cabinet_top_up_amount', 15, 2)->comment('Сумма пополнения');
            $table->text('payment_details')->nullable()->comment('Детали платежа');

            $table->foreignId('payment_id')
                ->constrained()
                ->onDelete('cascade')
                ->comment('Связанный платеж');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_operations');
    }
};
