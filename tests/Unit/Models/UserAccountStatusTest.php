<?php

namespace Tests\Unit\Models;

use App\Enums\UserAccountStatus;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccountStatusTest extends TestCase
{
    #[Test]
    public function account_status_maps_active_without_verified_email(): void
    {
        $user = new User(['is_active' => true, 'email_verified_at' => null]);

        $this->assertSame(UserAccountStatus::Active, $user->accountStatus());
    }

    #[Test]
    public function account_status_maps_active_with_verified_email(): void
    {
        $user = new User(['is_active' => true, 'email_verified_at' => now()]);

        $this->assertSame(UserAccountStatus::Active, $user->accountStatus());
    }

    #[Test]
    public function account_status_maps_pending_email(): void
    {
        $user = new User(['is_active' => false, 'email_verified_at' => null]);

        $this->assertSame(UserAccountStatus::PendingEmail, $user->accountStatus());
    }

    #[Test]
    public function account_status_maps_inactive(): void
    {
        $user = new User(['is_active' => false, 'email_verified_at' => now()]);

        $this->assertSame(UserAccountStatus::Inactive, $user->accountStatus());
    }
}
