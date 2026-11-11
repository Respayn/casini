<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class InvitationLandingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_invitation_login_opens_channels_instead_of_remembered_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $component = Livewire::test('pages::auth.set-password', ['user' => $user]);

        session([
            'url.intended' => route('system-settings.clients-and-projects.projects.manage', absolute: false),
        ]);

        $component->call('enterAccount')
            ->assertRedirect(route('channels', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('url.intended'));
    }

    public function test_email_confirmation_forgets_remembered_page(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        session([
            'url.intended' => route('system-settings.clients-and-projects.projects.manage', absolute: false),
        ]);

        $url = URL::temporarySignedRoute('register.verify', now()->addHour(), ['user' => $user->id]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertNull(session('url.intended'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
