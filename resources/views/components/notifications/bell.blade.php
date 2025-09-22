@php
    $svc = app(\App\Services\NotificationService::class);
    $unread = auth()->check() ? $svc->getUnreadCount(auth()->id()) : 0;
@endphp

<a href="{{ route('notifications.index') }}"
   class="relative ml-6 inline-flex h-8 w-8 items-center justify-center"
   title="Уведомления">
  <!-- иконка -->
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
       class="{{ $unread ? 'text-red-600' : 'text-gray-400' }}"
       width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8">
    <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 0 1-6 0"/>
  </svg>

  @if($unread)
    <span class="absolute -top-1 -right-1 rounded-full bg-red-600 text-white text-[10px] leading-none px-1.5 py-0.5">
      {{ $unread }}
    </span>
  @endif
</a>
