<?php

namespace Tests\Unit\Services\Statistics;

use App\Data\Statistics\StatisticsReportQueryData;
use App\Domain\Statistics\Enums\StatisticsReportDetailLevel;
use App\Enums\ChannelReportGrouping;
use App\Repositories\StatisticsReportAgencySettingsRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StatisticsReportAgencySettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('statistics_report_agency_settings')) {
            Schema::create('statistics_report_agency_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agency_id')->unique();
                $table->json('settings');
                $table->timestamps();
            });
        }
    }

    public function test_default_treat_missing_plan_as_unmet_is_yes(): void
    {
        $query = StatisticsReportQueryData::create();

        $this->assertSame('Y', $query->treatMissingPlanAsUnmet);
    }

    public function test_agency_modal_settings_payload_covers_all_fields(): void
    {
        $settings = StatisticsReportQueryData::create(StatisticsReportDetailLevel::BY_DAY);
        $settings->grouping = ChannelReportGrouping::CLIENTS;
        $settings->accumulateData = 'N';
        $settings->highlightUnmetKpi = 'Y';
        $settings->treatMissingPlanAsUnmet = 'N';

        $payload = $settings->agencyModalSettings();

        $this->assertSame([
            'grouping' => 'clients',
            'detailLevel' => 'by_day',
            'accumulateData' => 'N',
            'highlightUnmetKpi' => 'Y',
            'treatMissingPlanAsUnmet' => 'N',
        ], $payload);

        $other = StatisticsReportQueryData::create(StatisticsReportDetailLevel::BY_WEEK);
        $other->includeVat = false;
        $other->applyAgencyModalSettings($payload);

        $this->assertSame(ChannelReportGrouping::CLIENTS, $other->grouping);
        $this->assertSame(StatisticsReportDetailLevel::BY_DAY, $other->detailLevel);
        $this->assertSame('N', $other->accumulateData);
        $this->assertSame('Y', $other->highlightUnmetKpi);
        $this->assertSame('N', $other->treatMissingPlanAsUnmet);
        $this->assertFalse($other->includeVat);
    }

    public function test_agency_settings_repository_roundtrip(): void
    {
        $repo = new StatisticsReportAgencySettingsRepository;
        $agencyId = 900001;

        $repo->save($agencyId, [
            'grouping' => 'project_type',
            'detailLevel' => 'by_month',
            'accumulateData' => 'Y',
            'highlightUnmetKpi' => 'N',
            'treatMissingPlanAsUnmet' => 'Y',
        ]);

        $loaded = $repo->get($agencyId);

        $this->assertSame('project_type', $loaded['grouping']);
        $this->assertSame('by_month', $loaded['detailLevel']);
        $this->assertSame('Y', $loaded['accumulateData']);
        $this->assertSame('N', $loaded['highlightUnmetKpi']);
        $this->assertSame('Y', $loaded['treatMissingPlanAsUnmet']);
    }
}
