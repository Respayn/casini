<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yandex_direct_daily_spendings', function (Blueprint $table) {
            $table->comment('Дневной расход Яндекс.Директ по клиенто-проекту (account-level)');

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('date')
                ->comment('Локальный день агентства, за который снят расход');

            $table->decimal('cost_with_vat', 14, 2)
                ->default(0);

            $table->decimal('cost_without_vat', 14, 2)
                ->default(0);

            $table->timestamps();

            $table->unique(['project_id', 'date']);
            $table->index(['project_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yandex_direct_daily_spendings');
    }
};
