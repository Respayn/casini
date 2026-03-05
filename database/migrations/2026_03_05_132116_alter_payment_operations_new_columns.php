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
        Schema::table('payment_operations', function (Blueprint $table) {
            $table->text('payment_description')->nullable()->comment('Описание платежа');

            $table->boolean('was_opened')->default(false);
            $table->date('ad_cabinet_money_send_date')->nullable()->comment('Дата отправки денег в рекламный кабинет');
            $table->decimal('credit_amount', 10, 2)->comment('Сумма кредита');

            $table->foreignId('channel_id')
                ->nullable()
                ->constrained('channels')
                ->cascadeOnDelete()
                ->comment('Связанный канал');

            $table->decimal('cabinet_received_amount', 10, 2)->nullable()->comment('Сумма поступления в кабинет');

            $table->foreignId('manager_id')
                ->nullable()
                ->constrained('managers')
                ->cascadeOnDelete()
                ->comment('Связанный клиент');

            $table->foreignId('client_project_id')
                ->nullable()
                ->constrained('projects')
                ->cascadeOnDelete()
                ->comment('Связанный проект');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('sent_to_ad_cabinet')->default(false)->comment('Отправлен ли платеж в рекламный кабинет');
            $table->timestamp('sent_to_ad_cabinet_updated_at')->nullable();

            $table->foreignId('sent_to_ad_cabinet_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('invoice_issued')->default(false)->comment('Счет выставлен');
            $table->timestamp('invoice_issued_updated_at')->nullable();

            $table->foreignId('invoice_issued_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->decimal('fee', 10, 2)->nullable()->comment('Сумма сбора');
            $table->boolean('fee_included')->default(false)->comment('Сбор в копилке');
            $table->dateTime('fee_included_updated_at')->nullable();

            $table->foreignId('fee_included_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('include_fee_from_other_payments')->default(false)->comment('В данном платеже учитывается задолженность других платежей');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_operations', function (Blueprint $table) {
            $table->dropForeign(['channel_id']);
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['client_project_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['sent_to_ad_cabinet_updated_by']);
            $table->dropForeign(['invoice_issued_updated_by']);
            $table->dropForeign(['fee_included_updated_by']);

            $table->dropColumn([
                'payment_description',
                
                'was_opened',
                'ad_cabinet_money_send_date',
                'credit_amount',

                'channel_id',
                'cabinet_received_amount',

                'manager_id',
                'client_project_id',
                'created_by',

                'sent_to_ad_cabinet',
                'sent_to_ad_cabinet_updated_at',
                'sent_to_ad_cabinet_updated_by',

                'invoice_issued',
                'invoice_issued_updated_at',
                'invoice_issued_updated_by',

                'fee',
                'fee_included',
                'fee_included_updated_at',
                'fee_included_updated_by',

                'include_fee_from_other_payments',
            ]);
        });
    }
};
