<?php

namespace Tests\Unit\Enums;

use App\Enums\UserAccountStatus;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccountStatusTest extends TestCase
{
    #[Test]
    public function inactive_persistence_stamps_verified_at_when_missing(): void
    {
        $data = UserAccountStatus::Inactive->toPersistence(null);

        $this->assertFalse($data['is_active']);
        $this->assertNotNull($data['email_verified_at']);
    }

    #[Test]
    public function inactive_persistence_keeps_existing_verified_at(): void
    {
        $user = new User(['email_verified_at' => now()->subDay()]);
        $data = UserAccountStatus::Inactive->toPersistence($user);

        $this->assertFalse($data['is_active']);
        $this->assertArrayNotHasKey('email_verified_at', $data);
    }

    #[Test]
    public function pending_email_clears_verified_at(): void
    {
        $data = UserAccountStatus::PendingEmail->toPersistence(null);

        $this->assertFalse($data['is_active']);
        $this->assertNull($data['email_verified_at']);
    }

    #[Test]
    public function user_account_status_maps_flags_correctly(): void
    {
        $pending = new User(['is_active' => false, 'email_verified_at' => null]);
        $inactive = new User(['is_active' => false, 'email_verified_at' => now()]);
        $active = new User(['is_active' => true, 'email_verified_at' => null]);

        $this->assertSame(UserAccountStatus::PendingEmail, $pending->accountStatus());
        $this->assertSame(UserAccountStatus::Inactive, $inactive->accountStatus());
        $this->assertSame(UserAccountStatus::Active, $active->accountStatus());
    }
}
