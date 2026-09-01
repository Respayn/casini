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

    #[Test]
    public function is_active_casts_database_one_to_true(): void
    {
        $user = new User;
        $user->setRawAttributes(['is_active' => 1]);

        $this->assertSame(true, $user->is_active);
    }

    #[Test]
    public function is_active_casts_database_zero_to_false(): void
    {
        $user = new User;
        $user->setRawAttributes(['is_active' => 0]);

        $this->assertSame(false, $user->is_active);
    }

    #[Test]
    public function account_status_maps_active_from_database_one(): void
    {
        $user = new User;
        $user->setRawAttributes(['is_active' => 1, 'email_verified_at' => null]);

        $this->assertSame(UserAccountStatus::Active, $user->accountStatus());
    }

    #[Test]
    public function account_status_maps_inactive_from_database_zero(): void
    {
        $user = new User;
        $user->setRawAttributes(['is_active' => 0, 'email_verified_at' => now()]);

        $this->assertSame(UserAccountStatus::Inactive, $user->accountStatus());
    }
}
