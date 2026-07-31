<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function inactive_authenticated_user_is_logged_out_and_redirected_to_login(): void
    {
        $user = User::factory()->create([
            'login' => 'mw_inactive_user',
            'email' => 'mw_inactive_user@example.com',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('inactive', __('auth.inactive'));
        $this->assertGuest();
    }

    #[Test]
    public function pending_email_authenticated_user_gets_pending_message(): void
    {
        $user = User::factory()->unverified()->create([
            'login' => 'mw_pending_user',
            'email' => 'mw_pending_user@example.com',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('pending_email', __('auth.pending_email'));
        $this->assertGuest();
    }

    #[Test]
    public function active_authenticated_user_is_not_blocked(): void
    {
        $user = User::factory()->create([
            'login' => 'mw_active_user',
            'email' => 'mw_active_user@example.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
