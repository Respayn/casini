<?php

namespace Src\Planning\Application\Factories;

use Src\Planning\Domain\Factories\AbstractKpiParametersSchemaFactory;
use Src\Planning\Domain\ValueObjects\KpiParametersSchemaBuilder;
use Src\Shared\ValueObjects\ProjectType;
use Src\Shared\ValueObjects\Kpi;

class SeoPromotionKpiParametersSchemaFactory extends AbstractKpiParametersSchemaFactory
{
    public function supports(ProjectType $type, Kpi $kpi): bool
    {
        return $type === ProjectType::SEO_PROMOTION
            && in_array($kpi, [Kpi::TRAFFIC, Kpi::POSITIONS], true);
    }

    protected function configureParameters(KpiParametersSchemaBuilder $builder)
    {
        if ($this->kpi === Kpi::TRAFFIC) {
            $builder->addSimpleParameter('visits', 'Объем визитов', null, true);
        } elseif ($this->kpi === Kpi::POSITIONS) {
            $builder
                ->addSimpleParameter('top_percent', 'Процент позиций в ТОП', 'percent', true)
                ->addSimpleParameter('conversions', 'Конверсии', null, false);
        }
    }
}
