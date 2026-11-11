<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Services\IntegrationSync\IntegrationSyncIssueStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IntegrationSyncIssueStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_records_access_error_message_for_google_sheets(): void
    {
        [$project, $row] = $this->makeGoogleSheetsIntegration();

        app(IntegrationSyncIssueStatus::class)->recordFailure(
            (int) $project->id,
            'google_sheets',
            'Failed to refresh Google token: invalid_grant',
        );

        $row->refresh();

        $this->assertSame(
            IntegrationSyncIssueStatus::KIND_ACCESS,
            $row->settings[IntegrationSyncIssueStatus::KIND_KEY] ?? null,
        );
        $this->assertSame(
            'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
            $row->settings[IntegrationSyncIssueStatus::MESSAGE_KEY] ?? null,
        );
    }

    public function test_other_error_does_not_overwrite_access_error(): void
    {
        [$project, $row] = $this->makeGoogleSheetsIntegration([
            IntegrationSyncIssueStatus::KIND_KEY => IntegrationSyncIssueStatus::KIND_ACCESS,
            IntegrationSyncIssueStatus::MESSAGE_KEY => 'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
        ]);

        app(IntegrationSyncIssueStatus::class)->recordFailure(
            (int) $project->id,
            'google_sheets',
            'App\\Jobs\\ProcessIntegrationSyncItem has timed out.',
        );

        $row->refresh();

        $this->assertSame(
            IntegrationSyncIssueStatus::KIND_ACCESS,
            $row->settings[IntegrationSyncIssueStatus::KIND_KEY] ?? null,
        );
        $this->assertSame(
            'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
            $row->settings[IntegrationSyncIssueStatus::MESSAGE_KEY] ?? null,
        );
    }

    public function test_success_clears_issue(): void
    {
        [$project, $row] = $this->makeGoogleSheetsIntegration([
            IntegrationSyncIssueStatus::KIND_KEY => IntegrationSyncIssueStatus::KIND_OTHER,
            IntegrationSyncIssueStatus::MESSAGE_KEY => 'Не удалось обновить данные. Следующая попытка будет ночью.',
        ]);

        app(IntegrationSyncIssueStatus::class)->recordSuccess((int) $project->id, 'google_sheets');

        $row->refresh();

        $this->assertArrayNotHasKey(IntegrationSyncIssueStatus::KIND_KEY, $row->settings ?? []);
        $this->assertArrayNotHasKey(IntegrationSyncIssueStatus::MESSAGE_KEY, $row->settings ?? []);
    }

    public function test_merge_preserved_keeps_issue_keys(): void
    {
        $merged = IntegrationSyncIssueStatus::mergePreserved(
            [
                'document_id' => 'old',
                IntegrationSyncIssueStatus::KIND_KEY => IntegrationSyncIssueStatus::KIND_ACCESS,
                IntegrationSyncIssueStatus::MESSAGE_KEY => 'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
            ],
            [
                'document_id' => 'new',
            ],
        );

        $this->assertSame('new', $merged['document_id']);
        $this->assertSame(IntegrationSyncIssueStatus::KIND_ACCESS, $merged[IntegrationSyncIssueStatus::KIND_KEY]);
        $this->assertSame(
            'Доступ к Google Таблицы больше не действует. Откройте таблицу на почту служебной учётки.',
            $merged[IntegrationSyncIssueStatus::MESSAGE_KEY],
        );
    }

    /**
     * @param  array<string, mixed>  $extraSettings
     * @return array{0: Project, 1: IntegrationProject}
     */
    private function makeGoogleSheetsIntegration(array $extraSettings = []): array
    {
        $project = Project::factory()->create(['is_active' => true]);
        $integration = Integration::query()->where('code', 'google_sheets')->first()
            ?? Integration::query()->create([
                'code' => 'google_sheets',
                'name' => 'Google Таблицы',
                'category' => 'money',
            ]);

        $row = IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => array_merge([
                'sync_enabled_at' => now()->toDateString(),
            ], $extraSettings),
        ]);

        return [$project, $row];
    }
}
