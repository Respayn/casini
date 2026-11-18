<?php

namespace Tests\Feature\SystemSettings;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Src\Domain\ValueObjects\Kpi;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class ClientProjectFormLeadsAnalyticsVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    private function createUserWithAgency(): User
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        Permission::findOrCreate('full clients and projects all', 'web');
        $user->givePermissionTo('full clients and projects all');
        $user->load('permissions', 'roles');

        return $user;
    }

    public function test_context_traffic_hides_metrika_callibri_and_search_api(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::TRAFFIC->value);

        $codes = $component->instance()->analyticsIntegrations
            ->pluck('code')
            ->all();

        $this->assertNotContains('yandex_metrika', $codes);
        $this->assertNotContains('callibri', $codes);
        $this->assertNotContains('yandex_search_api', $codes);
        $this->assertSame([], $codes);
        $component->assertSee('Нет доступных интеграций для этого типа клиенто-проекта и KPI', false);
    }

    public function test_context_leads_keeps_metrika_and_callibri_but_hides_search_api(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::LEADS->value);

        $codes = $component->instance()->analyticsIntegrations
            ->pluck('code')
            ->all();

        $this->assertContains('yandex_metrika', $codes);
        $this->assertContains('callibri', $codes);
        $this->assertNotContains('yandex_search_api', $codes);
    }

    public function test_seo_keeps_search_api_in_analytics_list(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->set('clientProjectForm.kpi', Kpi::POSITIONS->value);

        $codes = $component->instance()->analyticsIntegrations
            ->pluck('code')
            ->all();

        $this->assertContains('yandex_search_api', $codes);
    }

    public function test_seo_hides_yandex_direct_from_tools_list(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->set('clientProjectForm.kpi', Kpi::TRAFFIC->value);

        $codes = $component->instance()->toolsIntegrations
            ->pluck('code')
            ->all();

        $this->assertNotContains('yandex_direct', $codes);
        $this->assertSame([], $codes);
        $component->assertSee('Нет доступных интеграций для этого типа клиенто-проекта и KPI', false);
    }

    public function test_seo_does_not_open_yandex_direct_modal(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->set('clientProjectForm.kpi', Kpi::POSITIONS->value)
            ->call('selectIntegration', 'yandex_direct')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show');
    }

    public function test_context_keeps_yandex_direct_in_tools_list(): void
    {
        $user = $this->createUserWithAgency();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::TRAFFIC->value);

        $codes = $component->instance()->toolsIntegrations
            ->pluck('code')
            ->all();

        $this->assertContains('yandex_direct', $codes);
    }

    public function test_context_does_not_open_search_api_modal(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::LEADS->value)
            ->call('selectIntegration', 'yandex_search_api')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show');
    }

    public function test_context_traffic_does_not_open_metrika_or_callibri_modal(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::TRAFFIC->value)
            ->call('selectIntegration', 'yandex_metrika')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show')
            ->call('selectIntegration', 'callibri')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show');
    }

    public function test_context_disables_hidden_integrations_on_prepare_save(): void
    {
        $user = $this->createUserWithAgency();
        $metrika = Integration::query()->where('code', 'yandex_metrika')->firstOrFail();
        $callibri = Integration::query()->where('code', 'callibri')->firstOrFail();
        $searchApi = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->set('clientProjectForm.kpi', Kpi::POSITIONS->value)
            ->call('setIntegrationSettings', $searchApi->id, [
                'is_enabled' => true,
                'regions' => [
                    [
                        'code' => 213,
                        'phrases' => ['тест'],
                    ],
                ],
            ]);

        $this->assertTrue((bool) $component->get("integrationSettings.{$searchApi->id}.isEnabled"));

        $component
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::LEADS->value)
            ->call('setIntegrationSettings', $metrika->id, [
                'is_enabled' => true,
                'oauth_token' => 'token',
                'counter_id' => 12345678,
                'goals' => [1],
            ])
            ->call('setIntegrationSettings', $callibri->id, [
                'is_enabled' => true,
                'project_id' => 'abc',
            ]);

        $this->assertTrue((bool) $component->get("integrationSettings.{$metrika->id}.isEnabled"));
        $this->assertTrue((bool) $component->get("integrationSettings.{$callibri->id}.isEnabled"));
        $this->assertTrue((bool) $component->get("integrationSettings.{$searchApi->id}.isEnabled"));

        $component->set('clientProjectForm.kpi', Kpi::TRAFFIC->value);

        $method = new ReflectionMethod($component->instance(), 'disableHiddenIntegrations');
        $method->invoke($component->instance());

        $this->assertFalse((bool) $component->get("integrationSettings.{$metrika->id}.isEnabled"));
        $this->assertFalse((bool) $component->get("integrationSettings.{$callibri->id}.isEnabled"));
        $this->assertFalse((bool) $component->get("integrationSettings.{$searchApi->id}.isEnabled"));
        $this->assertSame('token', $component->get("integrationSettings.{$metrika->id}.settings.oauth_token"));
        $this->assertSame('abc', $component->get("integrationSettings.{$callibri->id}.settings.project_id"));
    }

    public function test_seo_disables_yandex_direct_on_prepare_save(): void
    {
        $user = $this->createUserWithAgency();
        $direct = Integration::query()->where('code', 'yandex_direct')->firstOrFail();

        $component = Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.kpi', Kpi::LEADS->value)
            ->call('setIntegrationSettings', $direct->id, [
                'is_enabled' => true,
                'oauth_token' => 'direct-token',
                'client_login' => 'login',
            ]);

        $this->assertTrue((bool) $component->get("integrationSettings.{$direct->id}.isEnabled"));

        $component
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->set('clientProjectForm.kpi', Kpi::TRAFFIC->value);

        $method = new ReflectionMethod($component->instance(), 'disableHiddenIntegrations');
        $method->invoke($component->instance());

        $this->assertFalse((bool) $component->get("integrationSettings.{$direct->id}.isEnabled"));
        $this->assertSame('direct-token', $component->get("integrationSettings.{$direct->id}.settings.oauth_token"));
    }
}

