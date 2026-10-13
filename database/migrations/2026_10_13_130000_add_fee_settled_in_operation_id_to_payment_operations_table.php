<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_operations', function (Blueprint $table) {
            $table->foreignId('fee_settled_in_operation_id')
                ->nullable()
                ->after('included_fee_debt')
                ->constrained('payment_operations')
                ->nullOnDelete()
                ->comment('Операция, в которой учтен сбор этого платежа («Да, учесть»)');
        });
    }

    public function down(): void
    {
        Schema::table('payment_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fee_settled_in_operation_id');
        });
    }
};
