<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const RENAMED = [
        'Яндекс' => 'Яндекс Директ',
        'Google' => 'Google Ads',
        'Яндекс Геомедийная реклама' => 'Яндекс геомедийная реклама',
    ];

    public function up(): void
    {
        foreach (self::RENAMED as $old => $new) {
            DB::table('payment_operations')->where('advertising_system', $old)->update(['advertising_system' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::RENAMED as $old => $new) {
            DB::table('payment_operations')->where('advertising_system', $new)->update(['advertising_system' => $old]);
        }
    }
};
