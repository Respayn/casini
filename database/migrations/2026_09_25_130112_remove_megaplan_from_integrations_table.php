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
        $integrationId = DB::table('integrations')->where('code', 'megaplan')->value('id');

        if ($integrationId === null) {
            return;
        }

        DB::table('integration_project')->where('integration_id', $integrationId)->delete();
        DB::table('integrations')->where('id', $integrationId)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = now();

        DB::table('integrations')->updateOrInsert(
            ['code' => 'megaplan'],
            [
                'name' => 'Мегаплан',
                'category' => 'analytics',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
};
