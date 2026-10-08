<?php

namespace App\Livewire\Forms\SystemSettings\Agency;

use Illuminate\Validation\Validator;
use Livewire\Attributes\Validate;
use Livewire\Form;

class AgencySettingsForm extends Form
{
    public ?int $id = null;

    #[Validate('required|string|max:255', message: 'Название агентства обязательно')]
    public string $name = '';

    #[Validate('required|string|max:255', message: 'Выберите часовой пояс')]
    public string $timeZone = '';

    #[Validate('required|date_format:H:i', message: 'Укажите время в формате ЧЧ:ММ')]
    public string $directBudgetRefreshTime = '09:00';

    #[Validate('nullable|url|max:255', message: 'Введите корректный URL-адрес')]
    public ?string $url = null;

    #[Validate('nullable|email|max:255', message: 'Введите корректный email')]
    public ?string $email = null;

    #[Validate('nullable|string|max:255', message: 'Телефон должен быть строкой')]
    public ?string $phone = null;

    #[Validate('nullable|string|max:255', message: 'Адрес должен быть строкой')]
    public ?string $address = null;

    // Логотип обрабатываем через Livewire upload (отдельно)
    public $logo = null;

    public ?string $logoSrc = null;

    public ?string $bitrix24PortalUrl = null;

    public ?string $bitrix24Webhook = null;

    // Массив админов (чтение)
    public array $admins = [];

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'timeZone' => 'required|string|max:255',
            'directBudgetRefreshTime' => 'required|date_format:H:i',
            'url' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
            'bitrix24PortalUrl' => 'nullable|url|max:255',
            'bitrix24Webhook' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'bitrix24PortalUrl.url' => 'Введите корректный URL портала Битрикс24',
        ];
    }

    /**
     * Ошибки пары URL/вебхук (ключ — поле формы без префикса form.).
     *
     * @return array<string, string>
     */
    public function bitrixPairErrors(): array
    {
        $portalUrl = trim((string) ($this->bitrix24PortalUrl ?? ''));
        $webhook = trim((string) ($this->bitrix24Webhook ?? ''));
        $portalFilled = $portalUrl !== '';
        $webhookFilled = $webhook !== '';
        $errors = [];

        if ($portalFilled !== $webhookFilled) {
            $message = 'Укажите URL портала и вебхук вместе';
            if (! $portalFilled) {
                $errors['bitrix24PortalUrl'] = $message;
            }
            if (! $webhookFilled) {
                $errors['bitrix24Webhook'] = $message;
            }
        }

        if ($webhookFilled && ! str_contains($webhook, '/rest/')) {
            $errors['bitrix24Webhook'] = 'Укажите адрес входящего вебхука Битрикс24';
        }

        return $errors;
    }

    /**
     * Доп. проверки пары URL/вебхук — вызвать перед $this->validate() в компоненте.
     */
    public function prepareBitrixValidation(): void
    {
        $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                foreach ($this->bitrixPairErrors() as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            });
        });
    }

    /**
     * Заполняет форму из AgencyData или массива.
     */
    public function from($agency): void
    {
        $this->id = $agency->id ?? null;
        $this->name = $agency->name ?? '';
        $this->timeZone = $agency->timeZone ?? '';
        $this->directBudgetRefreshTime = $agency->directBudgetRefreshTime ?? '09:00';
        $this->url = $agency->url ?? null;
        $this->email = $agency->email ?? null;
        $this->phone = $agency->phone ?? null;
        $this->address = $agency->address ?? null;
        $this->logoSrc = $agency->logoSrc ?? null;
        $this->bitrix24PortalUrl = $agency->bitrix24PortalUrl ?? null;
        $this->bitrix24Webhook = $agency->bitrix24Webhook ?? null;

        $this->admins = collect($agency->users ?? [])->map(function ($admin) {
            return [
                'id' => is_object($admin) ? $admin->id : ($admin['id'] ?? null),
                'name' => is_object($admin) ? $admin->name : ($admin['name'] ?? null),
            ];
        })->toArray();
    }
}
