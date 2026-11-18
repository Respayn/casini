<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::landing')]
#[Title('Политика обработки данных - Касини')]
class PrivacyPage extends Component
{
    public function render()
    {
        return view('livewire.privacy-page');
    }
}
