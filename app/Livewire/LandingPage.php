<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::landing')]
#[Title('Касини — Управляйте KPI в digital-маркетинге')]
class LandingPage extends Component
{
    public function render()
    {
        return view('livewire.landing-page');
    }
}
