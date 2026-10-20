<?php

namespace Tests\Feature\Integration;

use App\Models\Agency;
use App\Models\Client;
use App\Models\CompletedWork;
use App\Models\Integration;
use App\Models\Project;
use App\Models\User;
use App\Services\Bitrix24\Bitrix24Client;
use App\Services\IntegrationSync\Collectors\Bitrix24WorksCollector;
use App\Services\IntegrationSync\IntegrationProjectCredentials;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Src\Domain\CompletedWorks\CompletedWorkRepositoryInterface;
use Tests\TestCase;

class Bitrix24WorksCollectorTest extends TestCase
{
    use DatabaseTransactions;

    private Project $project;

    private int $integrationId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('completed_works') || ! Schema::hasColumn('agencies', 'bitrix24_webhook')) {
            $this->markTestSkipped('Нет таблицы completed_works или колонок Битрикс24 у агентства');
        }

        $integration = Integration::query()->firstOrCreate(
            ['code' => 'bitrix24'],
            ['name' => 'Битрикс24', 'category' => 'money'],
        );
        $this->integrationId = (int) $integration->id;

        $agency = Agency::factory()->create([
            'time_zone' => 'Europe/Moscow',
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret-key/',
        ]);

        $specialist = User::factory()->create();
        $specialist->agencies()->attach($agency->id);

        $client = Client::factory()->create();
        $this->project = Project::factory()->create([
            'client_id' => $client->id,
            'specialist_id' => $specialist->id,
            'is_active' => true,
        ]);

        $this->saveSettings(false);
    }

    public function test_collects_closed_checklist_items_from_month_task(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), 'tasks.task.list.json')) {
                $tasks = ($request->data()['filter']['PARENT_ID'] ?? null) === 100
                    ? [
                        ['id' => '101', 'title' => 'SEO: сентябрь 2026'],
                        ['id' => '102', 'title' => 'SEO: тексты'],
                    ]
                    : [];

                return Http::response(['result' => ['tasks' => $tasks]]);
            }

            if (str_ends_with($request->url(), 'task.checklistitem.getlist.json')) {
                $taskId = $request->data()[0] ?? null;

                $this->assertSame(101, $taskId);

                return Http::response(['result' => [
                    ['TITLE' => 'написать тексты', 'IS_COMPLETE' => 'Y', 'TOGGLED_DATE' => '2026-09-10T12:00:00+03:00'],
                    ['TITLE' => 'ещё не готово', 'IS_COMPLETE' => 'N'],
                ]]);
            }

            return Http::response(['result' => []]);
        });

        $result = $this->collector()->collectRange($this->project->id, Carbon::parse('2026-09-29'), Carbon::parse('2026-09-29'));

        $this->assertTrue($result->ok, (string) $result->error);

        $titles = CompletedWork::query()->where('project_id', $this->project->id)->pluck('title')->all();

        $this->assertSame(['написать тексты'], $titles);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'task.commentitem.getlist'));
    }

    public function test_parses_comment_works_from_month_task_when_enabled(): void
    {
        $this->saveSettings(true);

        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), 'tasks.task.list.json')) {
                $tasks = ($request->data()['filter']['PARENT_ID'] ?? null) === 100
                    ? [['id' => '101', 'title' => 'SEO: сентябрь 2026']]
                    : [];

                return Http::response(['result' => ['tasks' => $tasks]]);
            }

            if (str_ends_with($request->url(), 'task.checklistitem.getlist.json')) {
                return Http::response(['result' => []]);
            }

            return Http::response(['result' => [
                ['POST_MESSAGE' => '💪 написать тексты', 'POST_DATE' => '2026-09-29T10:00:00+03:00'],
                ['POST_MESSAGE' => 'просто комментарий', 'POST_DATE' => '2026-09-29T11:00:00+03:00'],
            ]]);
        });

        $result = $this->collector()->collectRange($this->project->id, Carbon::parse('2026-09-29'), Carbon::parse('2026-09-29'));

        $this->assertTrue($result->ok, (string) $result->error);
        $this->assertSame(
            ['написать тексты'],
            CompletedWork::query()->where('project_id', $this->project->id)->pluck('title')->all(),
        );
    }

    public function test_repeated_collect_overwrites_the_day(): void
    {
        CompletedWork::query()->create([
            'project_id' => $this->project->id,
            'title' => 'старая работа',
            'completed_at' => '2026-09-15',
        ]);

        Http::fake(fn () => Http::response(['result' => ['tasks' => []]]));

        $result = $this->collector()->collectRange($this->project->id, Carbon::parse('2026-09-29'), Carbon::parse('2026-09-29'));

        $this->assertTrue($result->ok);
        $this->assertSame(0, CompletedWork::query()->where('project_id', $this->project->id)->count());
    }

    private function collector(): Bitrix24WorksCollector
    {
        return new Bitrix24WorksCollector(
            app(IntegrationProjectCredentials::class),
            new Bitrix24Client(pauseMs: 0),
            app(CompletedWorkRepositoryInterface::class),
        );
    }

    private function saveSettings(bool $parseCommentWorks): void
    {
        DB::table('integration_project')->updateOrInsert(
            [
                'project_id' => $this->project->id,
                'integration_id' => $this->integrationId,
            ],
            [
                'is_enabled' => true,
                'settings' => json_encode([
                    'root_task' => 'https://company.bitrix24.ru/company/personal/user/1/tasks/task/view/100/',
                    'search_query' => 'SEO:',
                    'parse_comment_works' => $parseCommentWorks,
                ]),
            ],
        );
    }
}
