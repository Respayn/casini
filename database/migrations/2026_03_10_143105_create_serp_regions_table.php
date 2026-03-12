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
        Schema::create('serp_regions', function (Blueprint $table) {
            $table->comment('Географические регионы поиска, привязанные к поисковой системе');

            $table->id();

            $table->foreignId('search_engine_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name')
                ->nullable(false);

            $table->string('code')
                ->nullable(false)
                ->unique();

            $table->string('language')
                ->comment('Язык интерфейса выдачи');

            $table->string('country_code')
                ->comment('Код страны ISO 3166-1 alpha-2 (RU, US, DE)');

            $table->string('geo_id')
                ->comment('Нативный идентификатор региона в поисковике');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
