<?php

namespace App\Livewire\Forms\SystemSettings\Dictionaries;

use Livewire\Attributes\Validate;
use Livewire\Form;

class CreatePromotionRegionForm extends Form
{
    #[Validate('required', message: "Название обязательно для заполнения")]
    public string $name = '';
}
