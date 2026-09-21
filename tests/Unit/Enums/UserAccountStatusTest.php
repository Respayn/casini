<?php

namespace Tests\Unit\Enums;

use App\Enums\UserAccountStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserAccountStatusTest extends TestCase
{
    #[Test]
    public function select_options_use_form_labels(): void
    {
        $this->assertSame(
            [
                ['label' => 'Активен', 'value' => 'active'],
                ['label' => 'Неактивен', 'value' => 'inactive'],
                ['label' => 'Подтвердить email', 'value' => 'pending_email'],
            ],
            UserAccountStatus::selectOptions(),
        );
    }

    #[Test]
    public function list_label_differs_from_form_label_for_active_inactive(): void
    {
        $this->assertSame('Активный', UserAccountStatus::Active->listLabel());
        $this->assertSame('Неактивный', UserAccountStatus::Inactive->listLabel());
        $this->assertSame('Подтвердить email', UserAccountStatus::PendingEmail->listLabel());
    }
}
