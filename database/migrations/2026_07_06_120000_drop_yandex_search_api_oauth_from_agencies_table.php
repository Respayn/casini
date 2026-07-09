<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'yandex_search_api_client_id',
                'yandex_search_api_client_secret',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->string('yandex_search_api_client_id')->nullable()->after('logo_src');
            $table->text('yandex_search_api_client_secret')->nullable()->after('yandex_search_api_client_id');
        });
    }
};
