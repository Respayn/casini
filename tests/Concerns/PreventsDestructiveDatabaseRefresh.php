<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;

/**
 * Блокирует migrate:fresh / migrate:refresh / db:wipe в тестах,
 * чтобы не затереть staging/production БД.
 *
 * Разрешение только при CASINI_ALLOW_DB_REFRESH=true и имени БД с суффиксом _test
 * (или :memory:).
 */
trait PreventsDestructiveDatabaseRefresh
{
    public function artisan($command, $parameters = [])
    {
        if ($this->isDestructiveDatabaseCommand(is_string($command) ? $command : null)) {
            $this->guardAgainstDestructiveDatabaseRefresh();
        }

        return parent::artisan($command, $parameters);
    }

    protected function setUpTraits()
    {
        $uses = class_uses_recursive(static::class);

        if (isset($uses[RefreshDatabase::class])
            || isset($uses[DatabaseMigrations::class])
            || isset($uses[DatabaseTruncation::class])
        ) {
            $this->guardAgainstDestructiveDatabaseRefresh();
        }

        parent::setUpTraits();
    }

    protected function isDestructiveDatabaseCommand(?string $command): bool
    {
        if ($command === null) {
            return false;
        }

        return in_array($command, ['migrate:fresh', 'migrate:refresh', 'db:wipe'], true);
    }

    protected function guardAgainstDestructiveDatabaseRefresh(): void
    {
        $database = (string) config(
            'database.connections.'.config('database.default').'.database',
            ''
        );

        if ($this->isProtectedDatabaseName($database)) {
            throw new RuntimeException(
                'Запрещено пересоздавать БД «'.$database.'» из тестов. '.
                'RefreshDatabase вызывает migrate:fresh и затирает данные staging. '.
                'Используйте DatabaseTransactions. '.
                'CASINI_ALLOW_DB_REFRESH=true разрешён только для отдельной БД с суффиксом _test.'
            );
        }

        $allow = filter_var(env('CASINI_ALLOW_DB_REFRESH', false), FILTER_VALIDATE_BOOLEAN);

        if (! $allow) {
            throw new RuntimeException(
                'Destructive DB refresh в тестах запрещён (CASINI_ALLOW_DB_REFRESH не true). '.
                'Используйте DatabaseTransactions вместо RefreshDatabase, '.
                'чтобы не затереть данные на staging.'
            );
        }
    }

    protected function isProtectedDatabaseName(string $database): bool
    {
        if ($database === '' || $database === ':memory:') {
            return false;
        }

        // Боевое/staging имя без суффикса _test — всегда блокируем, даже с флагом.
        return $database === 'casini' || ! str_ends_with($database, '_test');
    }
}
