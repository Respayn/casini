<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('template_name')->default('')->after('template_id');
        });

        $names = DB::table('templates')->pluck('name', 'id');

        foreach (DB::table('reports')->select('id', 'template_id')->orderBy('id')->cursor() as $report) {
            $name = (string) $names->get($report->template_id, '');
            if ($name === '') {
                continue;
            }

            DB::table('reports')->where('id', $report->id)->update([
                'template_name' => $name,
            ]);
        }

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->change();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('template_id')
                ->references('id')
                ->on('templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        DB::table('reports')->whereNull('template_id')->delete();

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable(false)->change();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')->on('templates');
            $table->dropColumn('template_name');
        });
    }
};
