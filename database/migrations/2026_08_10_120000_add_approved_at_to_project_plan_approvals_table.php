<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_plan_approvals', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('approved')->comment('Дата согласования');
        });

        DB::table('project_plan_approvals')
            ->where('approved', true)
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('COALESCE(updated_at, created_at, CURRENT_TIMESTAMP)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('project_plan_approvals', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
