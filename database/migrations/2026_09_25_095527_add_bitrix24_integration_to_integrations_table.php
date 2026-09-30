<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $exists = DB::table('integrations')->where('code', 'bitrix24')->exists();

        if ($exists) {
            DB::table('integrations')->where('code', 'bitrix24')->update([
                'name' => 'Битрикс24',
                'category' => 'money',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('integrations')->insert([
            'code' => 'bitrix24',
            'name' => 'Битрикс24',
            'category' => 'money',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('integrations')->where('code', 'bitrix24')->delete();
    }
};
