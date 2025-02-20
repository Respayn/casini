<?php

namespace App\Livewire\Forms\SystemSettings\Dictionaries;

use Livewire\Attributes\Validate;
use Livewire\Form;

class PromotionTopicForm extends Form
{
    public ?int $id = null;

    #[Validate('required', message: 'Категория обязательна')]
    public string $category = '';
    
    #[Validate('required', message: 'Тематика обязательна')]
    public string $topic = '';
}
