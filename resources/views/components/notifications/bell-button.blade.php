@php
    $svc = app(\App\Services\NotificationService::class);
    $unread = auth()->check() ? $svc->getUnreadCount(auth()->id()) : 0;
    $display = $unread > 99 ? '99+' : $unread;
@endphp

<div class="relative ml-2 inline-flex overflow-visible">
    <x-button.button
        href="{{ route('notifications.index') }}"
        icon="icons.bell"
        variant="outlined"
        rounded
    />
    @if($unread)
        <span class="notify-badge">{{ $display }}</span>
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

</style>