<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sao_performed_work_acts', function ($table) {
            $table->dropForeign(['project_id']);
        });

        DB::statement('ALTER TABLE sao_performed_work_acts MODIFY project_id BIGINT UNSIGNED NULL');

        Schema::table('sao_performed_work_acts', function ($table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('sao_performed_work_acts')
            ->whereNull('project_id')
            ->delete();

        Schema::table('sao_performed_work_acts', function ($table) {
            $table->dropForeign(['project_id']);
        });

        DB::statement('ALTER TABLE sao_performed_work_acts MODIFY project_id BIGINT UNSIGNED NOT NULL');

        Schema::table('sao_performed_work_acts', function ($table) {
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();
        });
    }
};
