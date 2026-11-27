<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('completed_works', function (Blueprint $table) {
            $table->text('title')->comment('Название выполненной работы')->change();
        });
    }

    public function down(): void
    {
        Schema::table('completed_works', function (Blueprint $table) {
            $table->string('title')->comment('Название выполненной работы')->change();
        });
    }
};
