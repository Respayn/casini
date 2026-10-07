@php
    $svc = app(\App\Services\NotificationService::class);
    $unread = auth()->check() ? $svc->getUnreadCount(auth()->id()) : 0;
    $display = $unread > 99 ? '99+' : $unread;
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
    <x-button.button
        href="{{ route('notifications.index') }}"
        icon="icons.bell"
        variant="outlined"
        rounded
    />
    @if($unread)
        <span class="notify-badge" :class="{ 'notify-badge-nudge': nudge }">{{ $display }}</span>
    @endif
</div>
<style>
.notify-badge {
  position: absolute;
  top: -6px;
  right: -12px;
  z-index: 1000;

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
</style>
