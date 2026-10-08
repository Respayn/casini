<?php

namespace Tests\Feature\Livewire;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Bitrix24ClientProjectIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('agencies') || ! Schema::hasColumn('agencies', 'bitrix24_webhook')) {
            $this->markTestSkipped('Нужна БД приложения с колонками bitrix24_* (MariaDB), не stub sqlite');
        }

        if (! Schema::hasTable('integrations')) {
            $this->markTestSkipped('Нужна таблица integrations');
        }

        $this->seed(IntegrationSeeder::class);
    }

    /**
     * @return array{0: User, 1: Agency}
     */
    private function createUserWithAgency(array $agencyAttributes = []): array
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create(array_merge([
            'bitrix24_portal_url' => null,
            'bitrix24_webhook' => null,
        ], $agencyAttributes));
        $user->agencies()->attach($agency->id);

        return [$user, $agency];
    }

    #[Test]
    public function test_money_list_contains_bitrix24(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form');

        $moneyNames = $component->instance()->moneyIntegrations->pluck('name')->all();

        $this->assertContains('Битрикс24', $moneyNames);
    }

    #[Test]
    public function test_bitrix24_button_disabled_without_agency_credentials(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        $html = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->html();

        $this->assertStringContainsString(
            'Сначала укажите URL портала и вебхук в настройках агентства',
            $html
        );

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'bitrix24')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show');
    }

    #[Test]
    public function test_select_bitrix24_opens_modal_when_agency_configured(): void
    {
        [$user, $agency] = $this->createUserWithAgency([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret/',
        ]);

        $html = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'bitrix24')
            ->assertSet('selectedIntegration.integration.code', 'bitrix24')
            ->assertDispatched('modal-show')
            ->html();

        $this->assertStringContainsString('URL корневой задачи', $html);
        $this->assertStringContainsString('SEO:', $html);
        $this->assertStringContainsString('КР:', $html);
        $this->assertStringContainsString('💪', $html);
    }

    #[Test]
    public function test_set_settings_rejects_enabled_without_root_task(): void
    {
        [$user, $agency] = $this->createUserWithAgency([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret/',
        ]);

        $integration = Integration::query()->where('code', 'bitrix24')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'root_task' => '',
                'search_query' => 'SEO:, КР:',
                'parse_comment_works' => false,
            ])
            ->assertHasErrors(['root_task']);

        $this->assertFalse($component->get('integrationSettings')->has($integration->id));
    }

    #[Test]
    public function test_set_settings_stores_search_query_as_entered(): void
    {
        [$user, $agency] = $this->createUserWithAgency([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret/',
        ]);

        $integration = Integration::query()->where('code', 'bitrix24')->firstOrFail();

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'root_task' => 'https://company.bitrix24.ru/tasks/task/view/42/',
                'search_query' => 'SEO:, КР:',
                'parse_comment_works' => true,
            ])
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->assertSet(
                "integrationSettings.{$integration->id}.settings.root_task",
                'https://company.bitrix24.ru/tasks/task/view/42/'
            )
            ->assertSet("integrationSettings.{$integration->id}.settings.search_query", 'SEO:, КР:')
            ->assertSet("integrationSettings.{$integration->id}.settings.parse_comment_works", true);
    }

    #[Test]
    public function test_set_settings_rejects_invalid_root_task_url(): void
    {
        [$user, $agency] = $this->createUserWithAgency([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => 'https://company.bitrix24.ru/rest/1/secret/',
        ]);

        $integration = Integration::query()->where('code', 'bitrix24')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'root_task' => 'просто текст',
                'search_query' => '',
                'parse_comment_works' => false,
            ])
            ->assertHasErrors(['root_task']);

        $this->assertFalse($component->get('integrationSettings')->has($integration->id));
    }
}
