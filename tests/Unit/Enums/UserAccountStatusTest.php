<?php

namespace Tests\Unit\Enums;

use App\Enums\UserAccountStatus;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccountStatusTest extends TestCase
{
    #[Test]
    public function from_flags_maps_statuses(): void
    {
        $this->assertSame(UserAccountStatus::Active, UserAccountStatus::fromFlags(true, null));
        $this->assertSame(UserAccountStatus::Active, UserAccountStatus::fromFlags(true, now()));
        $this->assertSame(UserAccountStatus::PendingEmail, UserAccountStatus::fromFlags(false, null));
        $this->assertSame(UserAccountStatus::Inactive, UserAccountStatus::fromFlags(false, now()));
    }

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
}
