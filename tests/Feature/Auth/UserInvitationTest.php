<?php

namespace Tests\Feature\Auth;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Services\Auth\UserInvitationService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    public function test_signed_link_matches_and_rejects_tamper(): void
    {
        $user = new User([
            'login' => 'ivan',
            'email' => 'ivan@example.ru',
            'is_active' => false,
        ]);
        $user->id = 42;

        $service = app(UserInvitationService::class);
        $url = $service->inviteUrl($user);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertTrue($service->hasValidSignature(
            $user,
            (string) $query['expires'],
            (string) $query['signature'],
        ));
        $this->assertTrue($service->canAccept($user));

        $this->assertFalse($service->hasValidSignature(
            $user,
            (string) $query['expires'],
            'deadbeef',
        ));
    }

    public function test_send_mails_the_invite_link(): void
    {
        Mail::fake();

        $user = new User([
            'login' => 'ivan',
            'email' => 'ivan@example.ru',
            'is_active' => false,
        ]);
        $user->id = 42;

        app(UserInvitationService::class)->send($user);

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($user): bool {
            return $mail->hasTo($user->email)
                && $mail->login === $user->login
                && str_contains($mail->inviteUrl, '/invitation/'.$user->id);
        });
    }
}
