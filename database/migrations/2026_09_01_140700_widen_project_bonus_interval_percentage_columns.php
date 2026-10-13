<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_bonus_intervals', function (Blueprint $table) {
            $table->decimal('from_percentage', 7, 2)->change();
            $table->decimal('to_percentage', 7, 2)->change();
            $table->decimal('bonus_percentage', 7, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('project_bonus_intervals', function (Blueprint $table) {
            $table->decimal('from_percentage', 5, 2)->change();
            $table->decimal('to_percentage', 5, 2)->change();
            $table->decimal('bonus_percentage', 5, 2)->nullable()->change();
        });
    }
};
