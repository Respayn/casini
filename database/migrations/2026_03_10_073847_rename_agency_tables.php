<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('agency_settings', 'agencies');
        Schema::rename('agency_admins', 'agency_user');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('agencies', 'agency_settings');
        Schema::rename('agency_user', 'agency_admins');
    }
};
