<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyRegistrationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.yandex.smartcaptcha.enabled', false);
        Config::set('app.locale', 'ru');
    }

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
            ->assertRedirect(route('channels', absolute: false));

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
            ->assertRedirect(route('channels', absolute: false));

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
    public function test_login_succeeds_when_active_without_verified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'login' => 'auth_active_unverified',
            'email' => 'auth_active_unverified@example.com',
            'password' => 'secret123',
            'is_active' => true,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_active_unverified')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('channels', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function test_pending_email_error_is_not_attached_to_user_login_field(): void
    {
        User::factory()->unverified()->create([
            'login' => 'auth_pending_field',
            'email' => 'auth_pending_field@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_pending_field')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['pending_email' => __('auth.pending_email')])
            ->assertHasNoErrors('userLogin');

        $this->assertGuest();
    }

    #[Test]
    public function test_wrong_password_for_inactive_user_does_not_reveal_account_status(): void
    {
        User::factory()->create([
            'login' => 'auth_inactive_wrong_pass',
            'email' => 'auth_inactive_wrong_pass@example.com',
            'password' => 'secret123',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_inactive_wrong_pass')
            ->set('password', 'bad-password')
            ->call('login')
            ->assertHasErrors(['userLogin' => __('auth.failed')])
            ->assertHasNoErrors('inactive')
            ->assertHasNoErrors('pending_email');

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
            'email_verified_at' => now(),
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_inactive')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['inactive' => __('auth.inactive')]);

        $this->assertGuest();
    }

    #[Test]
    public function test_login_fails_when_email_is_pending(): void
    {
        User::factory()->unverified()->create([
            'login' => 'auth_pending_email',
            'email' => 'auth_pending_email@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_pending_email')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['pending_email' => __('auth.pending_email')]);

        $this->assertGuest();
    }

    #[Test]
    public function test_resend_verification_email_for_pending_user(): void
    {
        Mail::fake();

        User::factory()->unverified()->create([
            'login' => 'auth_resend_pending',
            'email' => 'auth_resend_pending@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_resend_pending')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors(['pending_email'])
            ->call('resendVerificationEmail')
            ->assertSet('resendStatus', __('auth.verification_resent'))
            ->assertHasNoErrors('pending_email');

        Mail::assertSent(VerifyRegistrationMail::class, function (VerifyRegistrationMail $mail): bool {
            return $mail->hasTo('auth_resend_pending@example.com');
        });
    }

    #[Test]
    public function test_resend_verification_does_not_send_for_wrong_password(): void
    {
        Mail::fake();

        User::factory()->unverified()->create([
            'login' => 'auth_resend_wrong',
            'email' => 'auth_resend_wrong@example.com',
            'password' => 'secret123',
            'is_active' => false,
        ]);

        Livewire::test('pages::auth.login')
            ->set('userLogin', 'auth_resend_wrong')
            ->set('password', 'bad-password')
            ->call('resendVerificationEmail')
            ->assertSet('resendStatus', null);

        Mail::assertNothingSent();
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
            ->assertRedirect(route('channels', absolute: false));

        $this->assertAuthenticatedAs($user);
    }
}
