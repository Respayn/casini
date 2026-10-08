<?php

namespace Src\Planning\Application\Factories;

use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Src\Planning\Domain\Factories\AbstractKpiParametersSchemaFactory;
use Src\Planning\Domain\ValueObjects\KpiParametersSchemaBuilder;

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
                    'integer',
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
                    'integer',
                    true
                );
        }
    }
}
