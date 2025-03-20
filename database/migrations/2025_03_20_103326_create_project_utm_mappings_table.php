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
        Schema::create('project_utm_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->index()->comment('Идентификатор проекта');

            $table->enum('utm_type', ['utm_campaign'])->default('utm_campaign')->comment('Тип UTM');
            $table->string('utm_value', 255)->comment('Значение UTM');
            $table->string('replacement_value', 255)->comment('Замена значения');

            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->comment('Ссылка на таблицу проектов');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_utm_mappings');
    }
};
