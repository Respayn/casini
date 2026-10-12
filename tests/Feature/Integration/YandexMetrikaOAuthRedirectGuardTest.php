<?php

namespace Tests\Feature\Integration;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexMetrikaOAuthRedirectGuardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    #[Test]
    public function test_redirect_returns_unconfigured_view_for_popup_when_client_id_missing(): void
    {
        config([
            'services.yandex_metrika.client_id' => null,
            'services.yandex_metrika.client_secret' => null,
            'services.yandex_metrika.redirect_uri' => null,
        ]);

        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $response = $this->actingAs($user)->get(route('yandex-metrika.auth', [
            'project_id' => 1,
            'cache_data_id' => 'test',
            'popup' => 1,
        ]));

        $response->assertStatus(503);
        $response->assertSee('не настроена на сервере', false);
        $response->assertSee('yandex-metrika-oauth-error', false);
    }

    #[Test]
    public function test_redirect_includes_force_confirm_and_metrika_scope_when_configured(): void
    {
        config([
            'services.yandex_metrika.client_id' => 'test-metrika-client-id',
            'services.yandex_metrika.client_secret' => 'test-metrika-client-secret',
            'services.yandex_metrika.redirect_uri' => 'https://example.test/yandex-metrika/callback',
        ]);

        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $response = $this->actingAs($user)->get(route('yandex-metrika.auth', [
            'project_id' => 1,
            'cache_data_id' => 'test-session',
            'popup' => 1,
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('oauth.yandex.ru/authorize', $location);
        $this->assertStringContainsString('force_confirm=yes', $location);
        $this->assertStringContainsString('client_id=test-metrika-client-id', $location);
        $this->assertStringContainsString('login%3Aavatar', $location);
        $this->assertStringContainsString('login%3Ainfo', $location);
        $this->assertStringContainsString('metrika%3Aread', $location);
    }
}
