<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistics_report_agency_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->unique()->constrained('agencies')->cascadeOnDelete();
            $table->json('settings');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistics_report_agency_settings');
    }
};
