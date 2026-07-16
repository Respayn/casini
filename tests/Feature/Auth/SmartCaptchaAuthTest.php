<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SmartCaptchaAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex.smartcaptcha.enabled', true);
        Config::set('services.yandex.smartcaptcha.server_key', 'test-server-key');
        Config::set('services.yandex.smartcaptcha.client_key', 'test-client-key');
    }

    public function test_login_fails_when_captcha_token_is_invalid(): void
    {
        Http::fake([
            'smartcaptcha.yandexcloud.net/validate' => Http::response([
                'status' => 'failed',
                'message' => 'Invalid token',
            ], 200),
        ]);

        User::factory()->create([
            'login' => 'captcha_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'captcha_user')
            ->set('password', 'password')
            ->set('captchaToken', 'invalid-token')
            ->call('login')
            ->assertHasErrors(['captchaToken']);

        $this->assertGuest();
    }

    public function test_login_fails_when_captcha_token_is_empty(): void
    {
        Http::fake([
            'smartcaptcha.yandexcloud.net/validate' => Http::response([
                'status' => 'failed',
                'message' => 'Empty token',
            ], 200),
        ]);

        User::factory()->create([
            'login' => 'captcha_empty_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'captcha_empty_user')
            ->set('password', 'password')
            ->set('captchaToken', '')
            ->call('login')
            ->assertHasErrors(['captchaToken']);

        $this->assertGuest();
    }

    public function test_login_succeeds_when_captcha_is_valid(): void
    {
        Http::fake([
            'smartcaptcha.yandexcloud.net/validate' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $user = User::factory()->create([
            'login' => 'captcha_ok_user',
            'password' => 'password',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'captcha_ok_user')
            ->set('password', 'password')
            ->set('captchaToken', 'valid-token')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.dictionaries', absolute: false));

        $this->assertAuthenticatedAs($user);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'smartcaptcha.yandexcloud.net/validate')
                && $request['token'] === 'valid-token'
                && $request['secret'] === 'test-server-key';
        });
    }

    public function test_register_fails_when_captcha_is_invalid(): void
    {
        Http::fake([
            'smartcaptcha.yandexcloud.net/validate' => Http::response([
                'status' => 'failed',
            ], 200),
        ]);

        Livewire::test('pages::auth.register')
            ->set('step', 2)
            ->set('firstName', 'Иван')
            ->set('lastName', 'Иванов')
            ->set('agencyName', 'Агентство')
            ->set('timezone', 'Europe/Moscow')
            ->set('email', 'register-captcha@example.com')
            ->set('phone', '+7 (999) 123-45-67')
            ->set('password', 'pass12')
            ->set('passwordConfirmation', 'pass12')
            ->set('captchaToken', 'bad-token')
            ->call('register')
            ->assertHasErrors(['captchaToken'])
            ->assertSet('step', 2);
    }
}
