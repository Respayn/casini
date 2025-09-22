<div x-data="{ scrolled: 0 }"
     x-init="
       const el = $refs.list;
       const onScroll = () => scrolled = el.scrollTop / (el.scrollHeight - el.clientHeight) * 100;
       el.addEventListener('scroll', onScroll); onScroll();
     "
     class="min-h-screen bg-brand-bg">

  {{-- Верхняя белая плашка с округлением снизу-слева --}}
  <div class="bg-white h-20 rounded-bl-15 shadow-sm sticky top-0 z-10">
    <div class="max-w-6xl mx-auto h-full flex items-center justify-between px-6">
      <div class="flex items-center gap-3">
        <h2 class="text-xl font-semibold text-brand-title">Уведомления</h2>

        {{-- бейдж 99+ из макета --}}
        @php($unread = auth()->user()?->unreadNotifications()->count() ?? 0)
        @if($unread)
          <span class="inline-flex items-center justify-center text-white bg-red-500 border border-white rounded-full text-xs h-5 px-2">
            {{ $unread > 99 ? '99+' : $unread }}
          </span>
        @endif
      </div>

      <div class="flex items-center gap-2">
        {{-- Прочитать всё --}}
        @if($unread)
          <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="px-3 h-9 rounded-lg bg-brand-primary text-white text-sm hover:opacity-90">
              Отметить всё прочитанным
            </button>
          </form>
        @endif
        <button onclick="history.back()" class="text-brand.text hover:text-brand.title text-sm">Закрыть</button>
      </div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-6 py-6 grid grid-cols-[12px_1fr] gap-4">
    {{-- Левая «рельса» прогресса прокрутки как в фигме (Rectangle 45/46) --}}
    <div class="relative">
      <div class="w-[10px] rounded-full bg-brand.line h-full"></div>
      <div class="absolute left-[0px] top-0 w-[10px] rounded-full bg-brand.primary"
           :style="`height:${scrolled}%`"></div>
    </div>

    {{-- Список уведомлений --}}
    <div x-ref="list"
         class="bg-white rounded-lg border border-brand.line max-h-[70vh] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-brand.line">

      @forelse($notifications as $n)
        <div class="group border-b border-brand.line px-4 py-3 transition
                    {{ $n->read_at ? 'opacity-70 bg-white' : 'bg-[#E9F2FF]' }}">
          <div class="flex items-start gap-3">
            {{-- индикатор непрочитанного --}}
            <span class="mt-1 inline-block w-2 h-2 rounded-full
                         {{ $n->read_at ? 'bg-transparent' : 'bg-brand.primary' }}"></span>

            <div class="flex-1">
              <div class="text-sm leading-5 text-brand.title">
                {!! $n->text !!}
                @if($n->link_url)
                  {{-- кликом помечаем как прочитанное и редиректим на ссылку --}}
                  <form method="POST" action="{{ route('notifications.read-and-go', $n->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="url" value="{{ $n->link_url }}">
                    <button class="text-brand-primary underline underline-offset-2 hover:no-underline" target="_blank" rel="noopener">
                      перейти
                    </button>
                  </form>
                @endif
              </div>

              <div class="text-xs text-brand.text mt-1">
                {{ $n->created_at->format('d.m.Y H:i') }}
              </div>
            </div>

            {{-- кнопка «прочитано» для конкретной карточки --}}
            @if(!$n->read_at)
            <form method="POST" action="{{ route('notifications.read-one', $n->id) }}">
              @csrf
              <button class="opacity-70 hover:opacity-100 text-[12px] text-brand.text">прочитано</button>
            </form>
            @endif
          </div>
        </div>
      @empty
        <div class="text-brand.text py-10 text-center">Пока нет уведомлений</div>
      @endforelse
    </div>
  </div>
</div>
