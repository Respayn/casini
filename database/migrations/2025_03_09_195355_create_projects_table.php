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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название проекта');
            $table->string('domain')->comment('URL проекта');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->comment('ID клиента');
            $table->foreignId('specialist_id')->nullable()->constrained('users')->nullOnDelete()->comment('ID специалиста');
            $table->foreignId('department_id')->constrained()->comment('ID департамента');
            $table->enum('project_type', ['context_ad', 'seo_promotion'])->comment('Тип проекта');
            $table->enum('service_type', ['to_top', 'traffic', 'order', 'kr'])->comment('Тип сервиса');
            $table->string('kpi', 45)->default('simple')->comment('KPI');
            $table->boolean('is_internal')->default(false)->comment('Флаг внутреннего проекта');
            $table->boolean('is_active')->default(true)->comment('Активность проекта');
            $table->string('traffic_attribution')->nullable()->comment('Атрибуция трафика');
            $table->string('metrika_counter')->nullable()->comment('Счётчик метрики');
            $table->text('metrika_targets')->nullable()->comment('Цели Метрики');
            $table->string('google_ads_client_id')->nullable()->comment('ID клиента Google Ads');
            $table->string('contract_number')->nullable()->comment('Номер договора');
            $table->string('additional_contract_number')->nullable()->comment('Номер дополнительного соглашения');
            $table->string('recommendation_url')->nullable()->comment('URL рекомендаций');
            $table->string('legal_entity')->nullable()->comment('Юридическое лицо');
            $table->string('inn')->nullable()->comment('ИНН');
            $table->timestamps();

            // Индексы
            $table->index('domain');
            $table->index('is_active');
            $table->index('client_id');
            $table->index('specialist_id');
            $table->index('department_id');
            $table->index('project_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
