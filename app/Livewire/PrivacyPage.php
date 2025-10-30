<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::landing')]
class PrivacyPage extends Component
{
    public function render()
    {
        return view('livewire.privacy-page');
    }
}
