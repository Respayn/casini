<?php

namespace App\Livewire\Forms\SystemSettings\Dictionaries;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CreateRateForm extends Form
{
    #[Validate('required', message: "Название обязательно для заполнения")]
    public string $name = '';

    #[Validate('required', message: "Значение обязательно для заполнения")]
    #[Validate('numeric', message: "Значение должно быть числом")]
    public ?int $value = null;

    #[Validate('required', message: "Дата начала обязательна для заполнения")]
    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;
}
