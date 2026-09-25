<?php

namespace Tests\Feature\Livewire;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgencySettingsFormTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('agencies') || ! Schema::hasColumn('agencies', 'bitrix24_webhook')) {
            $this->markTestSkipped('Нужна БД приложения с колонками bitrix24_* (MariaDB), не stub sqlite');
        }

        if (! Schema::hasColumn('users', 'first_name')) {
            $this->markTestSkipped('Нужна актуальная схема users (first_name)');
        }
    }

    private function createUserWithAgency(): array
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create([
            'bitrix24_portal_url' => null,
            'bitrix24_webhook' => null,
        ]);
        $user->agencies()->attach($agency->id);

        return [$user, $agency];
    }

    private function sampleWebhook(): string
    {
        return 'https://company.bitrix24.ru/rest/1/abcSECRET99xyz/';
    }

    #[Test]
    public function test_renders_bitrix_section_with_tooltip_hint(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->assertStatus(200)
            ->assertSee('Интеграция с Битрикс24', false)
            ->assertSee('URL портала Битрикс24', false)
            ->assertSee('Вебхук', false)
            ->assertSee('Входящий вебхук', false)
            ->assertSee('Задачи', false)
            ->assertSee('Пользователи', false)
            ->assertSee('После настройки в настройках клиенто-проектов станет доступен Битрикс24 для настройки интеграции', false)
            ->assertSeeHtml('<strong class="font-semibold not-italic">После настройки в настройках клиенто-проектов станет доступен Битрикс24 для настройки интеграции.</strong>');
    }

    #[Test]
    public function test_saves_bitrix_pair_encrypted_and_reloads(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $webhook = $this->sampleWebhook();
        $portalUrl = 'https://company.bitrix24.ru';

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', $portalUrl)
            ->set('form.bitrix24Webhook', $webhook)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.agency', ['agency' => $agency->id]));

        $agency->refresh();
        $this->assertSame($portalUrl, $agency->bitrix24_portal_url);
        $this->assertSame($webhook, $agency->bitrix24_webhook);

        $rawWebhook = DB::table('agencies')->where('id', $agency->id)->value('bitrix24_webhook');
        $this->assertNotSame($webhook, $rawWebhook);
        $this->assertNotEmpty($rawWebhook);

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->assertSet('form.bitrix24PortalUrl', $portalUrl)
            ->assertSet('form.bitrix24Webhook', $webhook);
    }

    #[Test]
    public function test_saved_webhook_is_masked_in_page_markup(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $webhook = $this->sampleWebhook();
        $agency->update([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => $webhook,
        ]);

        $html = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->html();

        $this->assertStringContainsString("'*'.repeat(middleLen)", $html);
        $this->assertStringContainsString('value.slice(0, 8)', $html);
        $this->assertStringContainsString('value.slice(-6)', $html);
        $this->assertStringNotContainsString('value="'.$webhook.'"', $html);

        $middle = 'abcSECRET99';
        $this->assertStringContainsString($middle, $webhook);
        $this->assertStringNotContainsString('>'.$middle.'<', $html);
    }

    #[Test]
    public function test_empty_bitrix_pair_saves_without_errors(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $agency->update([
            'bitrix24_portal_url' => 'https://company.bitrix24.ru',
            'bitrix24_webhook' => $this->sampleWebhook(),
        ]);

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', '')
            ->set('form.bitrix24Webhook', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.agency', ['agency' => $agency->id]));

        $agency->refresh();
        $this->assertNull($agency->bitrix24_portal_url);
        $this->assertNull($agency->bitrix24_webhook);
    }

    #[Test]
    public function test_portal_url_without_webhook_fails_validation(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', 'https://company.bitrix24.ru')
            ->set('form.bitrix24Webhook', '')
            ->call('save')
            ->assertHasErrors(['form.bitrix24Webhook'])
            ->assertSee('Укажите URL портала и вебхук вместе', false);

        $agency->refresh();
        $this->assertNull($agency->bitrix24_portal_url);
        $this->assertNull($agency->bitrix24_webhook);
    }

    #[Test]
    public function test_webhook_without_portal_url_fails_validation(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $webhook = $this->sampleWebhook();

        $component = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', '')
            ->set('form.bitrix24Webhook', $webhook)
            ->call('save')
            ->assertHasErrors(['form.bitrix24PortalUrl']);

        $messages = implode(' ', $component->errors()->all());
        $this->assertStringContainsString('Укажите URL портала и вебхук вместе', $messages);
        $this->assertStringNotContainsString($webhook, $messages);

        $agency->refresh();
        $this->assertNull($agency->bitrix24_portal_url);
        $this->assertNull($agency->bitrix24_webhook);
    }

    #[Test]
    public function test_webhook_without_rest_path_fails_validation(): void
    {
        [$user, $agency] = $this->createUserWithAgency();
        $badWebhook = 'https://company.bitrix24.ru/hooks/secret-token';

        $component = Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', 'https://company.bitrix24.ru')
            ->set('form.bitrix24Webhook', $badWebhook)
            ->call('save')
            ->assertHasErrors(['form.bitrix24Webhook']);

        $messages = implode(' ', $component->errors()->all());
        $this->assertStringContainsString('входящего вебхука', $messages);
        $this->assertStringNotContainsString($badWebhook, $messages);
    }

    #[Test]
    public function test_invalid_portal_url_fails_validation(): void
    {
        [$user, $agency] = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->withSession(['current_agency_id' => $agency->id])
            ->test('pages::system-settings.agency-settings', ['agency' => $agency->id])
            ->set('form.bitrix24PortalUrl', 'not-a-url')
            ->set('form.bitrix24Webhook', $this->sampleWebhook())
            ->call('save')
            ->assertHasErrors(['form.bitrix24PortalUrl']);
    }
}
