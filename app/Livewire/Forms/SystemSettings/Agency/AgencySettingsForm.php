<?php

namespace App\Livewire\Forms\SystemSettings\Agency;


use Livewire\Attributes\Validate;
use Livewire\Form;

class AgencySettingsForm extends Form
{
    public ?int $id = null;

    #[Validate('required|string|max:255', message: 'Название агентства обязательно')]
    public string $name = '';

    #[Validate('required|string|max:255', message: 'Выберите часовой пояс')]
    public string $timeZone = '';

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

    // Массив админов (чтение)
    public array $admins = [];

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'timeZone' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:1024',
        ];
    }

    /**
     * Заполняет форму из AgencyData или массива.
     */
    public function from($agency)
    {
        $this->id = $agency->id ?? null;
        $this->name = $agency->name ?? '';
        $this->timeZone = $agency->timeZone ?? '';
        $this->url = $agency->url ?? null;
        $this->email = $agency->email ?? null;
        $this->phone = $agency->phone ?? null;
        $this->address = $agency->address ?? null;
        $this->logoSrc = $agency->logoSrc ?? null;

        $this->admins = collect($agency->admins ?? [])->map(function($admin) {
            return [
                'id' => is_object($admin) ? $admin->id : ($admin['id'] ?? null),
                'name' => is_object($admin) ? $admin->name : ($admin['name'] ?? null),
            ];
        })->toArray();
    }
}
