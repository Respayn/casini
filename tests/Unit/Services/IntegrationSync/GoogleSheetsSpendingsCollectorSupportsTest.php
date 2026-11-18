<?php

namespace Tests\Unit\Services\IntegrationSync;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\IntegrationProject;
use App\Models\Project;
use App\Models\User;
use App\Services\IntegrationSync\Collectors\GoogleSheetsSpendingsCollector;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleSheetsSpendingsCollectorSupportsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('agencies') || ! Schema::hasColumn('agencies', 'google_sheets_spreadsheet_url')) {
            $this->markTestSkipped('Нужна колонка agencies.google_sheets_spreadsheet_url');
        }

        $this->seed(IntegrationSeeder::class);
    }

    public function test_supports_enabled_project_when_agency_has_spreadsheet_url(): void
    {
        $specialist = User::factory()->create();
        $agency = Agency::factory()->create([
            'google_sheets_spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/sheet-abc/edit',
        ]);
        $specialist->agencies()->attach($agency->id);

        $project = Project::factory()->create([
            'is_active' => true,
            'specialist_id' => $specialist->id,
        ]);

        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();
        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => ['sync_enabled_at' => now()->toDateString()],
        ]);

        $this->assertTrue(app(GoogleSheetsSpendingsCollector::class)->supportsProject((int) $project->id));
    }

    public function test_does_not_support_without_agency_spreadsheet_url(): void
    {
        Agency::query()->update(['google_sheets_spreadsheet_url' => null]);

        $specialist = User::factory()->create();
        $agency = Agency::factory()->create([
            'google_sheets_spreadsheet_url' => null,
        ]);
        $specialist->agencies()->attach($agency->id);

        $project = Project::factory()->create([
            'is_active' => true,
            'specialist_id' => $specialist->id,
        ]);

        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();
        IntegrationProject::query()->create([
            'project_id' => $project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => [],
        ]);

        $this->assertFalse(app(GoogleSheetsSpendingsCollector::class)->supportsProject((int) $project->id));
    }
}
