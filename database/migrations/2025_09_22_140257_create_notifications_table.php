<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();

            // адресат
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();

            // содержание
            $t->text('text');                           // чистый текст без HTML
            $t->string('link_url', 2048)->nullable();   // одиночная ссылка (быстрый случай / совместимость)
            $t->json('links')->nullable();              // массив ссылок [{key,label,url}]

            // классификация/контекст
            $t->string('type', 64)->nullable()->index();
            $t->json('payload')->nullable();            // любые доп. поля

            // связь с объектом в системе (полиморфная)
            $t->string('notifiable_type', 255)->nullable();
            $t->unsignedBigInteger('notifiable_id')->nullable();

            // проект (если нужен фильтр по проектам)
            $t->unsignedBigInteger('project_id')->nullable()->index();

            // статус прочтения
            $t->timestamp('read_at')->nullable();

            $t->timestamps();

            // индексы под частые выборки
            $t->index(['notifiable_type','notifiable_id']);
            $t->index(['user_id','read_at']);
            $t->index(['user_id','id']);
            $t->index(['user_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
