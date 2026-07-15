<?php

namespace Tests\Feature\Integration;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectOAuthRedirectGuardTest extends TestCase
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
            'services.yandex_direct.client_id' => null,
            'services.yandex_direct.client_secret' => null,
            'services.yandex_direct.redirect_uri' => null,
        ]);

        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.redirect', [
            'project_id' => 1,
            'cache_data_id' => 'test',
            'popup' => 1,
        ]));

        $response->assertStatus(503);
        $response->assertSee('не настроена на сервере', false);
        $response->assertSee('yandex-direct-oauth-error', false);
    }

    #[Test]
    public function test_redirect_includes_force_confirm_when_oauth_configured(): void
    {
        config([
            'services.yandex_direct.client_id' => 'test-client-id',
            'services.yandex_direct.client_secret' => 'test-client-secret',
            'services.yandex_direct.redirect_uri' => 'https://example.test/yandex-direct/callback',
        ]);

        $user = User::factory()->create();
        $agency = Agency::factory()->create();
        $user->agencies()->attach($agency->id);

        $response = $this->actingAs($user)->get(route('yandex_direct.oauth.redirect', [
            'project_id' => 1,
            'cache_data_id' => 'test-session',
            'popup' => 1,
        ]));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('oauth.yandex.ru/authorize', $location);
        $this->assertStringContainsString('force_confirm=yes', $location);
        $this->assertStringContainsString('client_id=test-client-id', $location);
    }
}
