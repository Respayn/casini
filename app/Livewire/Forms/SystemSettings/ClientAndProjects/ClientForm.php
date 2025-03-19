<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use Livewire\Attributes\Rule;
use Livewire\Form;

class ClientForm extends Form
{
    public ?int $id = null;

    #[Rule('required|string|max:255', message: 'Название клиента обязательно')]
    public string $name = '';

    #[Rule('required|string|max:12', message: 'ИНН обязателен и должен содержать до 12 символов')]
    public string $inn = '';

    #[Rule('required|exists:users,id', message: 'Выберите менеджера')]
    public ?int $manager = null;

    #[Rule('required|numeric', message: 'Начальная статистика взаиморасчетов обязательна')]
    public ?float $initial_balance = 0.0;

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
