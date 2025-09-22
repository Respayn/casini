<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->text('text');
            $t->string('link_url', 2048)->nullable();
            $t->string('type', 64)->nullable()->index();
            $t->json('payload')->nullable();
            $t->unsignedBigInteger('project_id')->nullable()->index();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'read_at']);
            $t->index(['user_id', 'id']);
            $t->index(['user_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('notifications'); }
};
