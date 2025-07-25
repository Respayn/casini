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
        Schema::table('users', function (Blueprint $table) {
            // Статус пользователя
            $table->boolean('is_active')->default(true)->after('name')->comment('Статус активности пользователя');
            // Раздельные имя и фамилия (если планируется разделять)
            $table->string('first_name')->nullable()->after('name')->comment('Имя пользователя');
            $table->string('last_name')->nullable()->after('first_name')->comment('Фамилия пользователя');
            // Телефон
            $table->string('phone', 32)->nullable()->after('email')->comment('Телефон пользователя');
            // Фото
            $table->string('image_path')->nullable()->after('phone')->comment('Путь к фото пользователя');
            // Мегаплан
            $table->string('megaplan_id')->nullable()->after('image_path')->comment('ID пользователя в Мегаплан');
            // Уведомления
            $table->boolean('enable_important_notifications')->default(true)->after('megaplan_id')->comment('Важные уведомления');
            $table->boolean('enable_notifications')->default(true)->after('enable_important_notifications')->comment('Обновления сервиса и прочие уведомления');

            $table->dropColumn('name');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'first_name',
                'last_name',
                'phone',
                'image_path',
                'megaplan_id',
                'enable_important_notifications',
                'enable_notifications',
            ]);
            $table->string('name')->after('id');
        });
    }
};
