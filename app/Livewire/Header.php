<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class Header extends Component
{
    public int $notificationsTick = 0;

    #[On('notifications-read')]
    public function refreshNotificationBadge(): void
    {
        $this->notificationsTick++;
    }

    public function render()
    {
        return view('livewire.header');
    }
}
