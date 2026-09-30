<?php

namespace Tests\Feature\Integration;

use App\Models\Agency;
use App\Models\Bitrix24DailyLabor;
use App\Models\Client;
use App\Models\Integration;
use App\Models\Project;
use App\Models\User;
use App\Services\Bitrix24\Bitrix24Client;
use App\Services\IntegrationSync\Collectors\Bitrix24LaborCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Bitrix24LaborCollectorTest extends TestCase
{
    use DatabaseTransactions;

    private Project $project;

    private User $specialist;

    private User $manager;

    private User $analyst;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('bitrix24_daily_labor') || ! Schema::hasColumn('users', 'bitrix24_id')) {
            $this->markTestSkipped('Нет таблицы bitrix24_daily_labor или колонки users.bitrix24_id');
        }

        $integration = Integration::query()->firstOrCreate(
            ['code' => 'bitrix24'],
            ['name' => 'Битрикс24', 'category' => 'money'],
        );

        $agency = Agency::factory()->create([
            'time_zone' => 'Europe/Moscow',
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret-key/',
        ]);

        User::query()->whereIn('bitrix24_id', [987501, 987502, 987503, 987999])->update(['bitrix24_id' => null]);

        $this->specialist = $this->makeUser(987501, 'Базовая');
        $this->manager = $this->makeUser(987502, 'Базовая');
        $this->analyst = $this->makeUser(987503, 'Ставка аналитика');
        $this->specialist->agencies()->attach($agency->id);

        $client = Client::factory()->create(['manager_id' => $this->manager->id]);
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $this->specialist->id,
            'is_active' => true,
        ]);

        DB::table('integration_project')->insert([
            'project_id' => $this->project->id,
            'integration_id' => $integration->id,
            'is_enabled' => true,
            'settings' => json_encode([
                'root_task' => 'https://company.bitrix24.ru/company/personal/user/1/tasks/task/view/100/',
                'search_query' => 'SEO:',
                'parse_comment_works' => false,
            ]),
        ]);
    }

    public function test_collects_hours_by_role_and_skips_unknown_users(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), 'tasks.task.list.json')) {
                $tasks = ($request->data()['filter']['PARENT_ID'] ?? null) === 100
                    ? [['id' => '101', 'title' => 'SEO: аудит, октябрь 2026'], ['id' => '102', 'title' => 'Дизайн']]
                    : [];

                return Http::response(['result' => ['tasks' => $tasks]]);
            }

            $taskId = $request->data()[0] ?? null;

            return Http::response(['result' => $taskId === 101 ? [
                ['USER_ID' => '987501', 'SECONDS' => '3600', 'CREATED_DATE' => '2026-09-29T10:00:00+03:00'],
                ['USER_ID' => '987501', 'SECONDS' => '1800', 'CREATED_DATE' => '2026-09-29T15:00:00+03:00'],
                ['USER_ID' => '987502', 'SECONDS' => '7200', 'CREATED_DATE' => '2026-09-29T11:00:00+03:00'],
                ['USER_ID' => '987503', 'SECONDS' => '900', 'CREATED_DATE' => '2026-09-29T12:00:00+03:00'],
                ['USER_ID' => '987999', 'SECONDS' => '600', 'CREATED_DATE' => '2026-09-29T13:00:00+03:00'],
            ] : [
                ['USER_ID' => '987501', 'SECONDS' => '3600', 'CREATED_DATE' => '2026-09-29T10:00:00+03:00'],
            ]]);
        });

        $result = $this->collector()->collectRange($this->project->id, Carbon::parse('2026-09-29'), Carbon::parse('2026-09-29'));

        $this->assertTrue($result->ok, (string) $result->error);

        $rows = Bitrix24DailyLabor::query()->where('project_id', $this->project->id)->get()->keyBy('user_id');

        $this->assertCount(3, $rows);
        $this->assertSame('seo-specialist', $rows[$this->specialist->id]->role->value);
        $this->assertSame(5400, $rows[$this->specialist->id]->seconds);
        $this->assertSame('2026-09-29', $rows[$this->specialist->id]->date->toDateString());
        $this->assertSame('2026-10-01', $rows[$this->specialist->id]->report_month->toDateString());
        $this->assertSame('ork-manager', $rows[$this->manager->id]->role->value);
        $this->assertSame('analyst', $rows[$this->analyst->id]->role->value);
    }

    public function test_repeated_collect_overwrites_the_day(): void
    {
        Bitrix24DailyLabor::query()->create([
            'project_id' => $this->project->id,
            'date' => '2026-09-29',
            'report_month' => '2026-10-01',
            'user_id' => $this->specialist->id,
            'role' => 'seo-specialist',
            'seconds' => 99999,
        ]);

        Http::fake(fn () => Http::response(['result' => ['tasks' => []]]));

        $result = $this->collector()->collectRange($this->project->id, Carbon::parse('2026-09-29'), Carbon::parse('2026-09-29'));

        $this->assertTrue($result->ok);
        $this->assertSame(0, Bitrix24DailyLabor::query()->where('project_id', $this->project->id)->count());
    }

    private function collector(): Bitrix24LaborCollector
    {
        return new Bitrix24LaborCollector(
            app(IntegrationProjectCredentials::class),
            new Bitrix24Client(pauseMs: 0),
        );
    }

    private function makeUser(int $bitrixId, string $rateName): User
    {
        $user = User::factory()->create([
            'login' => 'bitrix_labor_'.$bitrixId.'_'.uniqid(),
            'bitrix24_id' => $bitrixId,
        ]);

        $rateId = DB::table('rates')->insertGetId(['name' => $rateName, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('rate_values')->insert(['rate_id' => $rateId, 'value' => 1000, 'start_date' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('rate_user')->insert(['user_id' => $user->id, 'rate_id' => $rateId, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => now()]);

        return $user;
    }
}
