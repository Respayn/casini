<?php

namespace App\Livewire\SystemSettings;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.system-settings')]
class DictionaryList extends Component
{
    public function render()
    {
        return view('livewire.system-settings.dictionary-list');
    }
}
