<?php

namespace Tests\Unit\Livewire\Forms\SystemSettings\Agency;

use App\Livewire\Forms\SystemSettings\Agency\AgencySettingsForm;
use Livewire\Component;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgencySettingsFormBitrixValidationTest extends TestCase
{
    private function makeForm(): AgencySettingsForm
    {
        $component = new class extends Component
        {
            public AgencySettingsForm $form;
        };

        return new AgencySettingsForm($component, 'form');
    }

    #[Test]
    public function test_empty_pair_has_no_errors(): void
    {
        $form = $this->makeForm();
        $form->bitrix24PortalUrl = null;
        $form->bitrix24Webhook = null;

        $this->assertSame([], $form->bitrixPairErrors());
    }

    #[Test]
    public function test_portal_without_webhook_requires_pair(): void
    {
        $form = $this->makeForm();
        $form->bitrix24PortalUrl = 'https://company.bitrix24.ru';
        $form->bitrix24Webhook = '';

        $errors = $form->bitrixPairErrors();

        $this->assertArrayHasKey('bitrix24Webhook', $errors);
        $this->assertSame('Укажите URL портала и вебхук вместе', $errors['bitrix24Webhook']);
        $this->assertArrayNotHasKey('bitrix24PortalUrl', $errors);
    }

    #[Test]
    public function test_webhook_without_portal_requires_pair(): void
    {
        $form = $this->makeForm();
        $form->bitrix24PortalUrl = '';
        $form->bitrix24Webhook = 'https://company.bitrix24.ru/rest/1/secret/';

        $errors = $form->bitrixPairErrors();

        $this->assertArrayHasKey('bitrix24PortalUrl', $errors);
        $this->assertSame('Укажите URL портала и вебхук вместе', $errors['bitrix24PortalUrl']);
        $this->assertStringNotContainsString('secret', implode(' ', $errors));
    }

    #[Test]
    public function test_webhook_without_rest_is_rejected(): void
    {
        $form = $this->makeForm();
        $form->bitrix24PortalUrl = 'https://company.bitrix24.ru';
        $form->bitrix24Webhook = 'https://company.bitrix24.ru/hooks/secret-token';

        $errors = $form->bitrixPairErrors();

        $this->assertArrayHasKey('bitrix24Webhook', $errors);
        $this->assertStringContainsString('входящего вебхука', $errors['bitrix24Webhook']);
        $this->assertStringNotContainsString('secret-token', $errors['bitrix24Webhook']);
    }

    #[Test]
    public function test_valid_pair_has_no_errors(): void
    {
        $form = $this->makeForm();
        $form->bitrix24PortalUrl = 'https://company.bitrix24.ru';
        $form->bitrix24Webhook = 'https://company.bitrix24.ru/rest/1/secret/';

        $this->assertSame([], $form->bitrixPairErrors());
    }
}
