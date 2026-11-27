<?php

namespace App\Services;

use App\Repositories\AgencyReportViewSettingsRepository;
use App\Support\SharedProductFilters;
use Illuminate\Support\Facades\Auth;

/**
 * Общие настройки «Настроить отчет» на агентство: группировка и округление копеек.
 */
class AgencyReportViewSettingsService
{
    public function __construct(
        private readonly AgencyReportViewSettingsRepository $repository = new AgencyReportViewSettingsRepository,
    ) {}

    public function currentAgencyId(): ?int
    {
        $sessionAgencyId = session('current_agency_id');
        if (filled($sessionAgencyId) && (int) $sessionAgencyId > 0) {
            return (int) $sessionAgencyId;
        }

        $fallback = Auth::user()?->agencies()->value('agencies.id');

        return filled($fallback) && (int) $fallback > 0
            ? (int) $fallback
            : null;
    }

    /**
     * Запись агентства → сессия SharedProductFilters (перезапись при каждом открытии экрана).
     */
    public function syncSessionFromAgency(?int $agencyId = null): void
    {
        $agencyId ??= $this->currentAgencyId();
        if ($agencyId === null) {
            return;
        }

        $settings = $this->repository->get($agencyId);
        if ($settings === null) {
            return;
        }

        if (isset($settings['grouping']) && is_string($settings['grouping'])) {
            SharedProductFilters::rememberGrouping($settings['grouping']);
        }

        if (array_key_exists('round_kopecks', $settings)) {
            SharedProductFilters::rememberRoundKopecks((bool) $settings['round_kopecks']);
        }
    }

    /**
     * Сохранить частичные поля, остальное из уже сохранённого не затирать.
     */
    public function save(?string $grouping = null, ?bool $roundKopecks = null, ?int $agencyId = null): void
    {
        $agencyId ??= $this->currentAgencyId();
        if ($agencyId === null) {
            return;
        }

        if ($grouping === null && $roundKopecks === null) {
            return;
        }

        $current = $this->repository->get($agencyId) ?? [];

        if ($grouping !== null) {
            $current['grouping'] = SharedProductFilters::normalizeGrouping($grouping);
        }

        if ($roundKopecks !== null) {
            $current['round_kopecks'] = $roundKopecks;
        }

        $this->repository->save($agencyId, $current);
    }
}
