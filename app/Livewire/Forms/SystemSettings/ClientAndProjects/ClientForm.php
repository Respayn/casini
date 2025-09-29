<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use Livewire\Attributes\Rule;
use Livewire\Form;

class ClientForm extends Form
{
    public ?int $id = null;

    public string $name = '';

    public string $inn = '';

    public ?int $manager = null;

    public ?float $initial_balance = 0.0;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'inn' => [
                'required',
                'regex:/^\d{10,12}$/',
                'unique:clients,inn,' . ($this->id ?: 'null')
            ],
            'manager' => 'required|exists:users,id',
            'initial_balance' => 'required|numeric',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Название клиента обязательно',
            'name.max' => 'Название клиента не может быть длиннее 255 символов',
            'inn.required' => 'ИНН клиента обязателен',
            'inn.regex' => 'Некорректный формат ИНН',
            'inn.unique' => 'Данный ИНН уже используется',
            'manager.required' => 'Выберите менеджера',
            'manager.exists' => 'Менеджер не найден',
            'initial_balance.required' => 'Начальная статистика взаиморасчетов обязательна',
            'initial_balance.numeric' => 'Начальная статистика взаиморасчетов должна быть числом',
        ];
    }

    /**
     * Метод для заполнения формы данными клиента.
     */
    public function from($client)
    {
        $this->id = $client->id;
        $this->name = $client->name;
        $this->inn = $client->inn;
        $this->manager = $client->manager_id;
        $this->initial_balance = $client->initial_balance;
    }
}
