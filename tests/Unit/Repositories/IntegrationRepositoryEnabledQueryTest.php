<?php

namespace Tests\Unit\Repositories;

use App\Repositories\IntegrationRepository;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IntegrationRepositoryEnabledQueryTest extends TestCase
{
    public function test_enabled_integrations_by_code_filters_enabled_and_code(): void
    {
        $sql = $this->pretendSql(
            fn () => app(IntegrationRepository::class)->getEnabledProjectIntegrationsByCode('yandex_metrika'),
        );

        $this->assertStringContainsString('from integration_project where is_enabled = 1', $sql);
        $this->assertStringContainsString("code = 'yandex_metrika'", $sql);
    }

    public function test_find_enabled_integration_filters_project_and_code(): void
    {
        $sql = $this->pretendSql(
            fn () => app(IntegrationRepository::class)->findEnabledProjectIntegration(5, 'google_sheets'),
        );

        $this->assertStringContainsString('where is_enabled = 1', $sql);
        $this->assertStringContainsString("code = 'google_sheets'", $sql);
        $this->assertStringContainsString('project_id = 5 limit 1', $sql);
    }

    /**
     * SQL без кавычек вокруг имён и с подставленными значениями: одинаково для MariaDB и SQLite.
     */
    private function pretendSql(callable $callback): string
    {
        $queries = DB::pretend($callback);

        $this->assertCount(1, $queries);

        $sql = $queries[0]['query'];

        foreach ($queries[0]['bindings'] as $binding) {
            $value = is_string($binding) ? "'".$binding."'" : (string) (int) $binding;
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return str_replace(['"', '`'], '', $sql);
    }
}
