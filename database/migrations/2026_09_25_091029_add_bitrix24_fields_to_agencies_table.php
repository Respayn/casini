<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('bitrix24_portal_url')->nullable()->after('logo_src');
            $table->text('bitrix24_webhook')->nullable()->after('bitrix24_portal_url');
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['bitrix24_portal_url', 'bitrix24_webhook']);
        });
    }
};
