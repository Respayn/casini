<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table) {
            $table->decimal('credit_amount', 15, 2)->default(0)
                ->comment('Кредит: минус - выдан клиенту, плюс - возврат кредита');
            $table->boolean('fee_included')->default(true)
                ->comment('Сбор удержан из этого платежа');
            $table->decimal('fee_amount', 15, 2)->default(0)->comment('Сбор с пополнения кабинета');
            $table->decimal('included_fee_debt', 15, 2)->default(0)
                ->comment('Задолженность по сбору, вычтенная в этом платеже');
            $table->decimal('ad_cabinet_amount', 15, 2)->default(0)
                ->comment('Поступит в рекламный кабинет (сбор учтен)');
            $table->date('ad_cabinet_sent_date')->nullable()->comment('Дата отправки средств в кабинет');
            $table->boolean('is_sent_to_cabinet')->default(false)->comment('Статус: платеж отправлен в кабинет');
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_fee_in_piggy_bank')->default(false)->comment('Сбор отправлен в копилку');
            $table->boolean('is_invoice_issued')->default(false)->comment('Счет из кабинета выставлен');
            $table->timestamp('invoice_changed_at')->nullable();
            $table->foreignId('invoice_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('advertising_system', 100)->nullable()->comment('Канал (рекламная система)');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable()->comment('Комментарий к платежу');
            $table->timestamp('opened_at')->nullable()->comment('Операцию открывали в отчете');
            $table->timestamp('unprocessed_notified_at')->nullable();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('ad_fee_type', 20)->default('three_percent')
                ->comment('Сбор с рекламного бюджета в ДРС');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('ad_fee_type');
        });

        Schema::table('payment_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_changed_by');
            $table->dropConstrainedForeignId('invoice_changed_by');
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn([
                'credit_amount',
                'fee_included',
                'fee_amount',
                'included_fee_debt',
                'ad_cabinet_amount',
                'ad_cabinet_sent_date',
                'is_sent_to_cabinet',
                'status_changed_at',
                'is_fee_in_piggy_bank',
                'is_invoice_issued',
                'invoice_changed_at',
                'advertising_system',
                'comment',
                'opened_at',
                'unprocessed_notified_at',
            ]);
        });
    }
};
