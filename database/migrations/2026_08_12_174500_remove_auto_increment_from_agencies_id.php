<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Снимаем AUTO_INCREMENT с agencies.id: новые агентства получают
     * случайный 4-значный id из приложения (AgencyIdGenerator).
     * Существующие строки не меняем.
     */
    public function up(): void
    {
        if (! Schema::hasTable('agencies')) {
            return;
        }

        // FK agency_user.agency_id → agencies.id мешает MODIFY без снятия ограничения
        $this->dropAgencyUserForeignKey();

        DB::statement('ALTER TABLE agencies MODIFY id BIGINT UNSIGNED NOT NULL');

        $this->restoreAgencyUserForeignKey();
    }

    public function down(): void
    {
        if (! Schema::hasTable('agencies')) {
            return;
        }

        $this->dropAgencyUserForeignKey();

        DB::statement('ALTER TABLE agencies MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        $this->restoreAgencyUserForeignKey();
    }

    private function dropAgencyUserForeignKey(): void
    {
        if (! Schema::hasTable('agency_user')) {
            return;
        }

        $fkName = $this->resolveAgencyUserForeignKeyName();
        if ($fkName === null) {
            return;
        }

        DB::statement("ALTER TABLE agency_user DROP FOREIGN KEY `{$fkName}`");
    }

    private function restoreAgencyUserForeignKey(): void
    {
        if (! Schema::hasTable('agency_user')) {
            return;
        }

        if ($this->resolveAgencyUserForeignKeyName() !== null) {
            return;
        }

        DB::statement(
            'ALTER TABLE agency_user
             ADD CONSTRAINT agency_user_agency_id_foreign
             FOREIGN KEY (agency_id) REFERENCES agencies (id)
             ON DELETE CASCADE'
        );
    }

    private function resolveAgencyUserForeignKeyName(): ?string
    {
        $database = DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME = ?
             LIMIT 1',
            [$database, 'agency_user', 'agency_id', 'agencies']
        );

        return $row->name ?? null;
    }
};
