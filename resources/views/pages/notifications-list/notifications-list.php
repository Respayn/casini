<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

new class extends Component
{
    public array $notifications = [];
    public int $unreadBefore = 0;

    private NotificationService $svc;

    // В Livewire так корректно внедрять зависимости
    public function boot(NotificationService $svc): void
    {
        $this->svc = $svc;
    }

    public function mount(): void
    {
        $uid = Auth::id();

        // показываем пользователю, сколько было непрочитанным до открытия
        $this->unreadBefore = $this->svc->getUnreadCount($uid);

        // грузим список после отметки
        $this->notifications = $this->svc->getUserNotifications($uid)->all();
        
        // по требованиям: при открытии страницы — все прочитаны
        $this->svc->markAllAsRead($uid);
    }
};
