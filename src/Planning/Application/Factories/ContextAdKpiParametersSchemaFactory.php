<?php

namespace Src\Planning\Application\Factories;

use Src\Planning\Domain\Factories\AbstractKpiParametersSchemaFactory;
use Src\Planning\Domain\ValueObjects\KpiParametersSchemaBuilder;
use Src\Shared\ValueObjects\ProjectType;
use Src\Shared\ValueObjects\Kpi;

class ContextAdKpiParametersSchemaFactory extends AbstractKpiParametersSchemaFactory
{
    public function supports(ProjectType $type, Kpi $kpi): bool
    {
        return $type === ProjectType::CONTEXT_AD
            && in_array($kpi, [Kpi::TRAFFIC, Kpi::LEADS], true);
    }

    protected function configureParameters(KpiParametersSchemaBuilder $builder)
    {
        if ($this->kpi === Kpi::TRAFFIC) {
            $builder
                ->addSimpleParameter('cpc', 'CPC', 'currency')
                ->addSimpleParameter('budget', 'Рекламный бюджет', 'currency')
                ->addCalculatedParameter(
                    'visits',
                    'Объем визитов',
                    'cpc > 0 ? budget / cpc : null',
                    ['budget', 'cpc'],
                    null,
                    true
                );
        } elseif ($this->kpi === Kpi::LEADS) {
            $builder
                ->addSimpleParameter('cpl', 'CPL', 'currency')
                ->addSimpleParameter('budget', 'Рекламный бюджет', 'currency')
                ->addCalculatedParameter(
                    'leads',
                    'Лиды',
                    'cpl > 0 ? budget / cpl : null',
                    ['budget', 'cpl'],
                    null,
                    true
                );
        }
    }
}
