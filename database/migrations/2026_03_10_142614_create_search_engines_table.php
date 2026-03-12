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
        Schema::create('search_engines', function (Blueprint $table) {
            $table->comment('Справочник поисковых систем');

            $table->id();

            $table->string('name')
                ->nullable(false);

            $table->string('code')
                ->nullable(false)
                ->unique();

            $table->string('base_url');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_engines');
    }
};
