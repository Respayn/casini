<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function test_login_succeeds_with_login_and_password(): void
    {
        $user = User::factory()->create([
            'login' => 'auth_login_user',
            'email' => 'auth_login_user@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_login_user')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.dictionaries', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function test_login_succeeds_with_email(): void
    {
        $user = User::factory()->create([
            'login' => 'auth_email_user',
            'email' => 'auth_email_user@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_email_user@example.com')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.dictionaries', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'login' => 'auth_wrong_pass',
            'email' => 'auth_wrong_pass@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_wrong_pass')
            ->set('password', 'bad-password')
            ->call('login')
            ->assertHasErrors(['userLogin' => __('auth.failed')]);

        $this->assertGuest();
    }

    #[Test]
    public function test_login_fails_when_user_is_inactive(): void
    {
        User::factory()->create([
            'login' => 'auth_inactive',
            'email' => 'auth_inactive@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_inactive')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['userLogin' => __('auth.inactive')]);

        $this->assertGuest();
    }

    #[Test]
    public function test_login_ignores_intended_landing_url(): void
    {
        $user = User::factory()->create([
            'login' => 'auth_intended_user',
            'email' => 'auth_intended_user@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        session(['url.intended' => route('landing', absolute: false)]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_intended_user')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('system-settings.dictionaries', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
