<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $t) {
            if (!Schema::hasColumn('notifications','notifiable_type')) {
                $t->string('notifiable_type')->after('type');
            }
            if (!Schema::hasColumn('notifications','notifiable_id')) {
                $t->unsignedBigInteger('notifiable_id')->after('notifiable_type');
            }
            if (!Schema::hasColumn('notifications','read_at')) {
                $t->timestamp('read_at')->nullable()->after('data');
            }
            // Индексы под частые выборки
            $t->index(['notifiable_type','notifiable_id','read_at','created_at'], 'notif_user_read_created_idx');
        });

        // ⚠️ Если сейчас id INT, а вы хотите как у Laravel (UUID):
        // создайте новую таблицу и переложите, либо оставьте INT и зарегистрируйте кастомную модель (см. ниже).
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $t) {
            $t->dropIndex('notif_user_read_created_idx');
            // колонки назад удалять по ситуации
        });
    }
};
