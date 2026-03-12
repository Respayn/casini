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
        Schema::table("sao_performed_work_acts", function (Blueprint $table) {
            $table->string("customer_address", 255)->nullable()->comment("Адрес заказчика");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("sao_performed_work_acts", function (Blueprint $table) {
            $table->dropColumn("customer_address");
        });
    }
};
