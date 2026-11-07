@php
    $svc = app(\App\Services\NotificationService::class);
    $unread = auth()->check() ? $svc->getUnreadCount(auth()->id()) : 0;
    $display = $unread > 99 ? '99+' : $unread;
    $refererPath = parse_url((string) request()->headers->get('referer'), PHP_URL_PATH);
    // Предыдущую страницу учитываем только когда шапка обновляется сама
    // (запрос Livewire). Иначе переход из уведомлений в настройки
    // оставляет колокольчик синим.
    $headerRefresh = request()->is('livewire-*/update');
    $onNotifications = request()->is('notifications')
        || ($headerRefresh && rtrim((string) $refererPath, '/') === '/notifications');
@endphp

<div
    class="relative ml-2 inline-flex overflow-visible"
    @if($unread)
        x-data="{ nudge: false }"
        x-init="
            const beat = () => { nudge = true; setTimeout(() => nudge = false, 1000) };
            beat();
            setInterval(beat, 20000);
        "
    @endif
>
    <a
        href="{{ route('notifications.index') }}"
        wire:navigate
        title="Уведомления"
        aria-label="Уведомления"
        @class([
            'inline-flex h-10 w-10 items-center justify-center rounded-md transition',
            'text-primary' => $onNotifications,
            'text-secondary-text hover:text-primary' => ! $onNotifications,
        ])
    >
        @if ($unread)
            <x-icons.notifications-active class="notify-ring" />
        @else
            <x-icons.notifications />
        @endif
    </a>
    @if($unread)
        <span class="notify-badge" :class="{ 'notify-badge-nudge': nudge }">{{ $display }}</span>
    @endif
</div>
<style>
.notify-badge {
  position: absolute;
  top: -6px;
  right: -12px;
  /* Над иконкой колокольчика, но ниже модалки (z-50), иначе счётчик светится поверх затемнения */
  z-index: 1;

  display: inline-flex;
  align-items: center;
  justify-content: center;

  min-width: 22px;
  padding: 3px 5px;

  background: #ff5959;
  color: #fff;
  border-radius: 9999px;

  font-size: 12px;
  font-weight: 400;
  line-height: 1;

  box-shadow: 0 0 0 2px #fff; /* обводка под фон шапки */
}

@keyframes notify-badge-nudge {
  0%, 100% { transform: scale(1); }
  20% { transform: scale(1.25); }
  40% { transform: scale(0.92); }
  60% { transform: scale(1.12); }
}

.notify-badge-nudge {
  animation: notify-badge-nudge 1s ease;
}

@keyframes notify-ring {
  0%, 78%, 100% { transform: rotate(0deg); }
  82% { transform: rotate(14deg); }
  86% { transform: rotate(-12deg); }
  90% { transform: rotate(8deg); }
  94% { transform: rotate(-5deg); }
  98% { transform: rotate(0deg); }
}

.notify-ring {
  transform-origin: 50% 15%;
  animation: notify-ring 6s ease-in-out infinite;
}
</style>
