<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class NotificationList extends Component
{
    public $notifications;

    public function boot(NotificationService $svc) { $this->svc = $svc; }

    public function mount()
    {
        $uid = Auth::id();
        $this->notifications = $this->svc->getUserNotifications($uid);
        $this->svc->markAllAsRead($uid);
    }

    public function render()
    {
        return view('livewire.notifications.list');
    }
}
