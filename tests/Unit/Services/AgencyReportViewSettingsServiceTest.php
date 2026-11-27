<?php

namespace Tests\Unit\Services;

use App\Repositories\AgencyReportViewSettingsRepository;
use App\Services\AgencyReportViewSettingsService;
use App\Support\SharedProductFilters;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgencyReportViewSettingsServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('agency_report_view_settings')) {
            Schema::create('agency_report_view_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->unique();
                $table->json('settings');
                $table->timestamps();
            });
        }

        session()->forget([
            SharedProductFilters::GROUPING_KEY,
            SharedProductFilters::ROUND_KOPECKS_KEY,
        ]);
    }

    public function test_save_and_get_merge_partial_fields(): void
    {
        $repository = new AgencyReportViewSettingsRepository;
        $service = new AgencyReportViewSettingsService($repository);

        $service->save(grouping: 'clients', roundKopecks: false, agencyId: 1);

        $this->assertSame(
            ['grouping' => 'clients', 'round_kopecks' => false],
            $repository->get(1),
        );

        $service->save(grouping: 'project_type', agencyId: 1);

        $this->assertSame(
            ['grouping' => 'project_type', 'round_kopecks' => false],
            $repository->get(1),
        );
    }

    public function test_sync_session_from_agency_overwrites_session(): void
    {
        $repository = new AgencyReportViewSettingsRepository;
        $service = new AgencyReportViewSettingsService($repository);

        SharedProductFilters::rememberGrouping('none');
        SharedProductFilters::rememberRoundKopecks(true);

        $service->save(grouping: 'clients', roundKopecks: false, agencyId: 7);
        $service->syncSessionFromAgency(7);

        $this->assertSame('clients', SharedProductFilters::resolveGrouping('none'));
        $this->assertFalse(SharedProductFilters::roundKopecks());
    }

    public function test_sync_does_nothing_when_no_agency_row(): void
    {
        $service = new AgencyReportViewSettingsService(new AgencyReportViewSettingsRepository);

        SharedProductFilters::rememberGrouping('none');
        $service->syncSessionFromAgency(999);

        $this->assertSame('none', SharedProductFilters::resolveGrouping('clients'));
    }
}
