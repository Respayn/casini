<?php

namespace Tests\Unit\Services\ClientProject;

use App\Services\ClientProject\ParameterCalculationSchemeBuilder;
use PHPUnit\Framework\Attributes\Test;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ParameterCalculationSchemeBuilderTest extends TestCase
{
    private ParameterCalculationSchemeBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ParameterCalculationSchemeBuilder();
    }

    #[Test]
    public function test_context_leads_with_direct_and_callibri(): void
    {
        $rows = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            ['yandex_direct', 'callibri']
        );

        $this->assertSame([
            [
                'code' => 'cpl',
                'label' => 'CPL',
                'scheme' => 'Яндекс Директ, расходы / Callibri, ЕЖЛ',
            ],
            [
                'code' => 'budget',
                'label' => 'Рекламный бюджет',
                'scheme' => 'Яндекс Директ, расходы',
            ],
            [
                'code' => 'leads',
                'label' => 'Лиды',
                'scheme' => 'Callibri, ЕЖЛ',
            ],
        ], $rows);

        $this->assertStringNotContainsString('Calibri', $rows[0]['scheme']);
    }

    #[Test]
    public function test_context_leads_with_direct_callibri_and_metrika(): void
    {
        $rows = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            ['yandex_direct', 'callibri', 'yandex_metrika']
        );

        $this->assertSame(
            'Яндекс Директ, расходы / (Callibri, ЕЖЛ + Яндекс Метрика, достижение целей из отчета UTM-метки)',
            $rows[0]['scheme']
        );
        $this->assertSame(
            'Callibri, ЕЖЛ + Яндекс Метрика, достижение целей из отчета UTM-метки',
            $rows[2]['scheme']
        );
    }

    #[Test]
    public function test_context_leads_only_direct_leaves_leads_not_configured(): void
    {
        $rows = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            ['yandex_direct']
        );

        $byCode = collect($rows)->keyBy('code');

        $this->assertSame(ParameterCalculationSchemeBuilder::NOT_CONFIGURED, $byCode['cpl']['scheme']);
        $this->assertSame('Яндекс Директ, расходы', $byCode['budget']['scheme']);
        $this->assertSame(ParameterCalculationSchemeBuilder::NOT_CONFIGURED, $byCode['leads']['scheme']);
    }

    #[Test]
    public function test_search_api_does_not_affect_cpl_budget_or_leads(): void
    {
        $withoutSearchApi = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            ['yandex_direct', 'callibri']
        );

        $withSearchApi = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            ['yandex_direct', 'callibri', 'yandex_search_api']
        );

        $this->assertSame($withoutSearchApi, $withSearchApi);
    }

    #[Test]
    public function test_context_traffic_with_direct(): void
    {
        $rows = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::TRAFFIC,
            ['yandex_direct']
        );

        $this->assertSame('Яндекс Директ, расходы / Яндекс Директ, клики', $rows[0]['scheme']);
        $this->assertSame('CPC', $rows[0]['label']);
        $this->assertSame('Яндекс Директ, расходы', $rows[1]['scheme']);
        $this->assertSame('Яндекс Директ, клики', $rows[2]['scheme']);
    }

    #[Test]
    public function test_seo_positions_uses_search_api_only_for_top_percent(): void
    {
        $rows = $this->builder->build(
            ProjectType::SEO_PROMOTION,
            Kpi::POSITIONS,
            ['yandex_search_api', 'yandex_metrika']
        );

        $byCode = collect($rows)->keyBy('code');

        $this->assertSame('Yandex Search API', $byCode['top_percent']['scheme']);
        $this->assertSame(
            'Яндекс Метрика, достижение целей из отчета Поисковые системы',
            $byCode['conversions']['scheme']
        );
    }

    #[Test]
    public function test_empty_integrations_return_not_configured(): void
    {
        $rows = $this->builder->build(
            ProjectType::CONTEXT_AD,
            Kpi::LEADS,
            []
        );

        foreach ($rows as $row) {
            $this->assertSame(ParameterCalculationSchemeBuilder::NOT_CONFIGURED, $row['scheme']);
        }
    }
}
