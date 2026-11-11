<?php

namespace Tests\Unit\Infrastructure\Reports;

use PHPUnit\Framework\TestCase;
use Src\Infrastructure\Reports\CampaignDisplayName;

class CampaignDisplayNameTest extends TestCase
{
    public function test_uses_utm_label_when_campaign_id_matches(): void
    {
        $this->assertSame(
            'МК/Банные чаны/ РФ',
            CampaignDisplayName::resolve(100, 'Кампания А', ['100' => 'МК/Банные чаны/ РФ']),
        );
    }

    public function test_keeps_direct_name_without_mapping(): void
    {
        $this->assertSame(
            'Кампания А',
            CampaignDisplayName::resolve(100, 'Кампания А', ['200' => 'Другая']),
        );
        $this->assertSame(
            'Кампания А',
            CampaignDisplayName::resolve(100, 'Кампания А', []),
        );
        $this->assertSame(
            'Кампания А',
            CampaignDisplayName::resolve(null, 'Кампания А', ['100' => 'Подпись']),
        );
    }

    public function test_ignores_blank_replacement(): void
    {
        $this->assertSame(
            'Кампания А',
            CampaignDisplayName::resolve(100, 'Кампания А', ['100' => '  ']),
        );
    }
}
