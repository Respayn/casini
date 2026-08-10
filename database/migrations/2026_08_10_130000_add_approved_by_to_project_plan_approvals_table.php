<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_plan_approvals', function (Blueprint $table) {
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Кто согласовал');
        });
    }

    public function down(): void
    {
        Schema::table('project_plan_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
        });
    }
};
