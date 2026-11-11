<?php

namespace Tests\Feature\Livewire;

use App\Models\Agency;
use App\Models\Integration;
use App\Models\User;
use App\Services\GoogleSheets\GoogleSheetsServiceAccountCredentials;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Src\Domain\ValueObjects\ProjectType;
use Tests\TestCase;

class GoogleSheetsClientProjectIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private ?string $tempKeyPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('agencies') || ! Schema::hasColumn('agencies', 'google_sheets_spreadsheet_url')) {
            $this->markTestSkipped('Нужна БД приложения с колонкой google_sheets_spreadsheet_url');
        }

        if (! Schema::hasTable('integrations')) {
            $this->markTestSkipped('Нужна таблица integrations');
        }

        $this->seed(IntegrationSeeder::class);
    }

    protected function tearDown(): void
    {
        if ($this->tempKeyPath !== null && is_file($this->tempKeyPath)) {
            @unlink($this->tempKeyPath);
        }

        parent::tearDown();
    }

    /**
     * @return array{0: User, 1: Agency}
     */
    private function createUserWithAgency(array $agencyAttributes = []): array
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create(array_merge([
            'google_sheets_spreadsheet_url' => null,
        ], $agencyAttributes));
        $user->agencies()->attach($agency->id);

        Permission::findOrCreate('full clients and projects all', 'web');
        $user->givePermissionTo('full clients and projects all');
        $user->load('permissions', 'roles');

        return [$user, $agency];
    }

    private function putServiceAccountKey(): void
    {
        $this->tempKeyPath = tempnam(sys_get_temp_dir(), 'gsa');
        file_put_contents($this->tempKeyPath, json_encode([
            'client_email' => 'robot@casini-test.iam.gserviceaccount.com',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIE\n-----END PRIVATE KEY-----\n",
        ], JSON_THROW_ON_ERROR));

        Config::set('services.google_sheets.service_account_json', $this->tempKeyPath);
        app()->forgetInstance(GoogleSheetsServiceAccountCredentials::class);
    }

    /**
     * @return \Livewire\Features\SupportTesting\Testable
     */
    private function openClientProjectForm(User $user, Agency $agency)
    {
        session(['current_agency_id' => $agency->id]);

        return Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form');
    }

    #[Test]
    public function test_money_list_contains_google_sheets(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        $moneyNames = $this->openClientProjectForm($user, $agency)
            ->instance()
            ->moneyIntegrations
            ->pluck('name')
            ->all();

        $this->assertContains('Google Таблицы', $moneyNames);
    }

    #[Test]
    public function test_google_sheets_button_disabled_without_agency_url_and_key(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        $html = $this->openClientProjectForm($user, $agency)->html();

        $this->assertStringContainsString(
            'Сначала укажите ссылку на таблицу в настройках агентства и положите ключ служебной учётки на сервер',
            $html
        );

        $this->openClientProjectForm($user, $agency)
            ->call('selectIntegration', 'google_sheets')
            ->assertSet('selectedIntegration', null)
            ->assertNotDispatched('modal-show');
    }

    #[Test]
    public function test_select_google_sheets_opens_modal_when_agency_configured(): void
    {
        $this->putServiceAccountKey();
        [$user, $agency] = $this->createUserWithAgency([
            'google_sheets_spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);

        $html = $this->openClientProjectForm($user, $agency)
            ->call('selectIntegration', 'google_sheets')
            ->assertSet('selectedIntegration.integration.code', 'google_sheets')
            ->assertDispatched('modal-show')
            ->html();

        $this->assertStringContainsString('Синхронизация', $html);
        $this->assertStringNotContainsString('Войти через Google', $html);
        $this->assertStringNotContainsString('ID Google таблицы', $html);
    }

    #[Test]
    public function test_seo_project_type_enables_google_sheets_by_default(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();

        $component = $this->openClientProjectForm($user, $agency)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value);

        $this->assertTrue((bool) $component->get("integrationSettings.{$integration->id}.isEnabled"));
        $this->assertNotEmpty(
            $component->get("integrationSettings.{$integration->id}.settings.sync_enabled_at")
        );
    }

    #[Test]
    public function test_context_project_type_does_not_auto_enable_google_sheets(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();

        $component = $this->openClientProjectForm($user, $agency)
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value);

        $this->assertFalse($component->get('integrationSettings')->has($integration->id));
    }

    #[Test]
    public function test_switching_from_seo_to_context_removes_auto_enabled_google_sheets(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();

        $component = $this->openClientProjectForm($user, $agency)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value);

        $this->assertFalse($component->get('integrationSettings')->has($integration->id));
    }

    #[Test]
    public function test_manually_disabled_google_sheets_is_not_reenabled_on_seo(): void
    {
        $this->putServiceAccountKey();
        [$user, $agency] = $this->createUserWithAgency([
            'google_sheets_spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);
        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();

        $component = $this->openClientProjectForm($user, $agency)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => false,
            ])
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", false)
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value);

        $this->assertTrue($component->get('integrationSettings')->has($integration->id));
        $this->assertFalse((bool) $component->get("integrationSettings.{$integration->id}.isEnabled"));
    }

    #[Test]
    public function test_removed_google_sheets_is_not_restored_for_seo_after_type_refresh(): void
    {
        $this->putServiceAccountKey();
        [$user, $agency] = $this->createUserWithAgency([
            'google_sheets_spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
        ]);
        $integration = Integration::query()->where('code', 'google_sheets')->firstOrFail();

        $component = $this->openClientProjectForm($user, $agency)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value)
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->call('removeIntegration', $integration->id);

        $this->assertFalse(
            $component->instance()->configuredMoneyIntegrations->has($integration->id),
            'После удаления карточка не должна показываться в списке'
        );
        $this->assertTrue($component->get('integrationSettings')->has($integration->id));
        $this->assertFalse((bool) $component->get("integrationSettings.{$integration->id}.isEnabled"));
        $this->assertTrue((bool) $component->get("integrationSettings.{$integration->id}.settings.manually_removed"));

        $component
            ->set('clientProjectForm.projectType', ProjectType::CONTEXT_AD->value)
            ->set('clientProjectForm.projectType', ProjectType::SEO_PROMOTION->value);

        $this->assertFalse(
            $component->instance()->configuredMoneyIntegrations->has($integration->id),
            'SEO не должен возвращать удалённые Google Таблицы'
        );
        $this->assertFalse((bool) $component->get("integrationSettings.{$integration->id}.isEnabled"));
        $this->assertTrue((bool) $component->get("integrationSettings.{$integration->id}.settings.manually_removed"));
    }
}
