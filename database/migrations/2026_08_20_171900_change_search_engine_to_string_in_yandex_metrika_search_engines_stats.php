<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE yandex_metrika_search_engines_stats MODIFY search_engine VARCHAR(255) NOT NULL COMMENT 'Поисковая система'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE yandex_metrika_search_engines_stats MODIFY search_engine ENUM('yandex', 'google', 'other') NOT NULL COMMENT 'Поисковая система'");
    }
};
