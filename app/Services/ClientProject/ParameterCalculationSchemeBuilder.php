<?php

namespace App\Services\ClientProject;

use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;

/**
 * Собирает человекочитаемые схемы расчёта фактических параметров
 * по KPI/типу проекта и включённым интеграциям (как в Статистике).
 */
class ParameterCalculationSchemeBuilder
{
    public const NOT_CONFIGURED = 'Не настроено';

    public const SOURCE_DIRECT_SPEND = 'Яндекс Директ, расходы';

    public const SOURCE_DIRECT_CLICKS = 'Яндекс Директ, клики';

    public const SOURCE_CALLIBRI = 'Callibri, ЕЖЛ';

    public const SOURCE_METRIKA_UTM_GOALS = 'Яндекс Метрика, достижение целей из отчета UTM-метки';

    public const SOURCE_METRIKA_SEARCH_VISITS = 'Яндекс Метрика, переходы из отчета Поисковые системы';

    public const SOURCE_METRIKA_SEARCH_GOALS = 'Яндекс Метрика, достижение целей из отчета Поисковые системы';

    public const SOURCE_SEARCH_API = 'Yandex Search API';

    /**
     * @param  list<string>  $enabledIntegrationCodes
     * @return list<array{code: string, label: string, scheme: string}>
     */
    public function build(ProjectType $projectType, Kpi $kpi, array $enabledIntegrationCodes): array
    {
        $codes = array_values(array_unique(array_filter(
            $enabledIntegrationCodes,
            fn ($code) => is_string($code) && $code !== ''
        )));

        return match (true) {
            $projectType === ProjectType::CONTEXT_AD && $kpi === Kpi::TRAFFIC => $this->buildContextTraffic($codes),
            $projectType === ProjectType::CONTEXT_AD && $kpi === Kpi::LEADS => $this->buildContextLeads($codes),
            $projectType === ProjectType::SEO_PROMOTION && $kpi === Kpi::POSITIONS => $this->buildSeoPositions($codes),
            $projectType === ProjectType::SEO_PROMOTION && $kpi === Kpi::TRAFFIC => $this->buildSeoTraffic($codes),
            default => [],
        };
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, label: string, scheme: string}>
     */
    private function buildContextTraffic(array $codes): array
    {
        $hasDirect = in_array('yandex_direct', $codes, true);
        $budget = $hasDirect ? self::SOURCE_DIRECT_SPEND : self::NOT_CONFIGURED;
        $visits = $hasDirect ? self::SOURCE_DIRECT_CLICKS : self::NOT_CONFIGURED;
        $cpc = ($hasDirect)
            ? self::SOURCE_DIRECT_SPEND.' / '.self::SOURCE_DIRECT_CLICKS
            : self::NOT_CONFIGURED;

        return [
            $this->row('cpc', 'CPC', $cpc),
            $this->row('budget', 'Рекламный бюджет', $budget),
            $this->row('visits', 'Визитов', $visits),
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, label: string, scheme: string}>
     */
    private function buildContextLeads(array $codes): array
    {
        $budget = in_array('yandex_direct', $codes, true)
            ? self::SOURCE_DIRECT_SPEND
            : self::NOT_CONFIGURED;

        $leadParts = [];
        if (in_array('callibri', $codes, true)) {
            $leadParts[] = self::SOURCE_CALLIBRI;
        }
        if (in_array('yandex_metrika', $codes, true)) {
            $leadParts[] = self::SOURCE_METRIKA_UTM_GOALS;
        }

        $leads = $this->joinParts($leadParts);
        $cpl = $this->divideScheme($budget, $leadParts);

        return [
            $this->row('cpl', 'CPL', $cpl),
            $this->row('budget', 'Рекламный бюджет', $budget),
            $this->row('leads', 'Лиды', $leads),
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, label: string, scheme: string}>
     */
    private function buildSeoPositions(array $codes): array
    {
        $topPercent = in_array('yandex_search_api', $codes, true)
            ? self::SOURCE_SEARCH_API
            : self::NOT_CONFIGURED;

        $conversions = in_array('yandex_metrika', $codes, true)
            ? self::SOURCE_METRIKA_SEARCH_GOALS
            : self::NOT_CONFIGURED;

        return [
            $this->row('top_percent', '% позиций в топ 10', $topPercent),
            $this->row('conversions', 'Конверсии', $conversions),
        ];
    }

    /**
     * @param  list<string>  $codes
     * @return list<array{code: string, label: string, scheme: string}>
     */
    private function buildSeoTraffic(array $codes): array
    {
        $hasMetrika = in_array('yandex_metrika', $codes, true);

        return [
            $this->row(
                'visits',
                'Объем визитов',
                $hasMetrika ? self::SOURCE_METRIKA_SEARCH_VISITS : self::NOT_CONFIGURED
            ),
            $this->row(
                'conversions',
                'Конверсии',
                $hasMetrika ? self::SOURCE_METRIKA_SEARCH_GOALS : self::NOT_CONFIGURED
            ),
        ];
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinParts(array $parts): string
    {
        if ($parts === []) {
            return self::NOT_CONFIGURED;
        }

        return implode(' + ', $parts);
    }

    /**
     * @param  list<string>  $denominatorParts
     */
    private function divideScheme(string $numerator, array $denominatorParts): string
    {
        if ($numerator === self::NOT_CONFIGURED || $denominatorParts === []) {
            return self::NOT_CONFIGURED;
        }

        $denominator = implode(' + ', $denominatorParts);
        if (count($denominatorParts) > 1) {
            $denominator = '('.$denominator.')';
        }

        return $numerator.' / '.$denominator;
    }

    /**
     * @return array{code: string, label: string, scheme: string}
     */
    private function row(string $code, string $label, string $scheme): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'scheme' => $scheme,
        ];
    }
}
