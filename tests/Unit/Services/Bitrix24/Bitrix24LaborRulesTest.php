<?php

namespace Tests\Unit\Services\Bitrix24;

use App\Enums\LaborRole;
use App\Models\Bitrix24DailyLabor;
use App\Services\Bitrix24\Bitrix24LaborRoleResolver;
use App\Services\Bitrix24\Bitrix24TaskSelection;
use App\Services\Channels\ChannelLaborSpendingsService;
use App\Services\Rates\UserRateHistory;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class Bitrix24LaborRulesTest extends TestCase
{
    public function test_task_id_is_taken_from_task_url(): void
    {
        $this->assertSame(123, Bitrix24TaskSelection::taskIdFromUrl('https://company.bitrix24.ru/company/personal/user/1/tasks/task/view/123/'));
        $this->assertSame(77, Bitrix24TaskSelection::taskIdFromUrl('https://company.bitrix24.ru/workgroups/group/5/tasks/task/view/77/?IFRAME=Y'));
        $this->assertNull(Bitrix24TaskSelection::taskIdFromUrl('https://company.bitrix24.ru/company/personal/user/1/'));
    }

    public function test_empty_search_query_matches_any_title(): void
    {
        $this->assertTrue(Bitrix24TaskSelection::titleMatches('Любая задача', ''));
        $this->assertTrue(Bitrix24TaskSelection::titleMatches('Любая задача', ' , '));
    }

    public function test_title_must_contain_one_of_comma_separated_pieces(): void
    {
        $this->assertTrue(Bitrix24TaskSelection::titleMatches('SEO: аудит сайта', 'SEO:, КР:'));
        $this->assertTrue(Bitrix24TaskSelection::titleMatches('КР: запуск кампании', 'SEO:, КР:'));
        $this->assertTrue(Bitrix24TaskSelection::titleMatches('seo: мелкие правки', 'SEO:, КР:'));
        $this->assertFalse(Bitrix24TaskSelection::titleMatches('Разработка: форма', 'SEO:, КР:'));
    }

    public function test_report_month_is_taken_from_task_title(): void
    {
        $workDate = Carbon::parse('2026-09-28');

        $this->assertSame('2026-10-01', Bitrix24TaskSelection::reportMonth('SEO подзадача, октябрь 2026', $workDate)->toDateString());
        $this->assertSame('2026-08-01', Bitrix24TaskSelection::reportMonth('КР: отчёт за Август', $workDate)->toDateString());
        $this->assertSame('2026-05-01', Bitrix24TaskSelection::reportMonth('Правки в мае 2026', $workDate)->toDateString());
        $this->assertSame('2026-09-01', Bitrix24TaskSelection::reportMonth('SEO: маяк и мартышка', $workDate)->toDateString());
    }

    public function test_report_month_without_year_takes_nearest_year(): void
    {
        $this->assertSame('2026-12-01', Bitrix24TaskSelection::reportMonth('SEO декабрь', Carbon::parse('2027-01-10'))->toDateString());
        $this->assertSame('2027-01-01', Bitrix24TaskSelection::reportMonth('SEO январь', Carbon::parse('2026-12-28'))->toDateString());
    }

    public function test_analyst_rate_wins_over_project_role(): void
    {
        $role = Bitrix24LaborRoleResolver::resolve(5, 'Ставка Аналитика', specialistId: 5, managerId: 5, assistantIds: [5]);

        $this->assertSame(LaborRole::Analyst, $role);
    }

    public function test_role_follows_project_priority(): void
    {
        $this->assertSame(LaborRole::SeoSpecialist, Bitrix24LaborRoleResolver::resolve(5, 'Базовая', 5, 5, [5]));
        $this->assertSame(LaborRole::OrkManager, Bitrix24LaborRoleResolver::resolve(5, 'Базовая', 1, 5, [5]));
        $this->assertSame(LaborRole::SeoAssistant, Bitrix24LaborRoleResolver::resolve(5, null, 1, 2, [5]));
        $this->assertNull(Bitrix24LaborRoleResolver::resolve(5, null, 1, 2, [3]));
    }

    public function test_rate_is_taken_for_the_day_of_record(): void
    {
        $rates = new UserRateHistory(
            [7 => [['rate_id' => 1, 'assigned_at' => '2026-01-01'], ['rate_id' => 2, 'assigned_at' => '2026-09-15']]],
            [
                1 => ['name' => 'Базовая', 'values' => [['value' => 1000.0, 'start_date' => '2026-01-01']]],
                2 => ['name' => 'Старший', 'values' => [
                    ['value' => 1500.0, 'start_date' => '2026-01-01'],
                    ['value' => 2000.0, 'start_date' => '2026-09-20'],
                ]],
            ],
        );

        $this->assertSame(['name' => 'Базовая', 'value' => 1000.0], $rates->rateAt(7, '2026-09-10'));
        $this->assertSame(['name' => 'Старший', 'value' => 1500.0], $rates->rateAt(7, '2026-09-16'));
        $this->assertSame(['name' => 'Старший', 'value' => 2000.0], $rates->rateAt(7, '2026-09-25'));
        $this->assertNull($rates->rateAt(99, '2026-09-25'));
    }

    public function test_hours_are_multiplied_by_rate_per_day_and_summed_by_role(): void
    {
        $rates = new UserRateHistory(
            [7 => [['rate_id' => 1, 'assigned_at' => '2026-01-01']]],
            [1 => ['name' => 'Базовая', 'values' => [
                ['value' => 1000.0, 'start_date' => '2026-01-01'],
                ['value' => 1200.0, 'start_date' => '2026-09-20'],
            ]]],
        );

        $result = ChannelLaborSpendingsService::aggregate([
            $this->laborRow(10, '2026-09-10', 7, LaborRole::SeoSpecialist, 5400),
            $this->laborRow(10, '2026-09-25', 7, LaborRole::SeoSpecialist, 3600),
            $this->laborRow(10, '2026-09-25', 8, LaborRole::OrkManager, 1800),
        ], $rates);

        $this->assertSame(['hours' => 2.5, 'sum' => 2700.0], $result[10]['seo-specialist']);
        $this->assertSame(['hours' => 0.5, 'sum' => 0.0], $result[10]['ork-manager']);
        $this->assertArrayNotHasKey('analyst', $result[10]);
    }

    private function laborRow(int $projectId, string $date, int $userId, LaborRole $role, int $seconds): Bitrix24DailyLabor
    {
        $row = new Bitrix24DailyLabor;
        $row->setRawAttributes([
            'project_id' => $projectId,
            'date' => $date,
            'user_id' => $userId,
            'role' => $role->value,
            'seconds' => $seconds,
        ]);

        return $row;
    }
}
