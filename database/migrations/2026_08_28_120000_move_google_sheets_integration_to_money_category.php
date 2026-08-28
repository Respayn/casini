<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('integrations')
            ->where('code', 'google_sheets')
            ->update(['category' => 'money']);
    }

    public function down(): void
    {
        DB::table('integrations')
            ->where('code', 'google_sheets')
            ->update(['category' => 'analytics']);
    }
};
