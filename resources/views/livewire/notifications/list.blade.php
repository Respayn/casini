<div class="bg-transparent">
  <div class="sticky top-0 z-10 bg-white py-3 -mt-3">
    <div class="mx-auto flex items-center justify-between">
      <div class="text-[20px] leading-[100%] font-semibold text-[#283544]">Уведомления:</div>
      <button onclick="history.back()" class="text-[16px] font-normal text-slate-400 hover:text-[#283544]">Закрыть</button>
    </div>
  </div>

  <div class="mx-auto py-3">
    <div class="bg-white rounded-[10px] max-h-[calc(100vh-220px)] overflow-y-auto">
      <div class="border-y border-[#D0DDEE] divide-y divide-[#D0DDEE]">

        @forelse($notifications as $n)
          @php
            $isRead       = (bool) $n->read_at;
            $projectName  = data_get($n->payload ?? [], 'project');
            // URL проекта без домена по реальному роуту
            $projectUrl   = $n->project_id
              ? route('system-settings.clients-and-projects.projects.manage', ['projectId' => $n->project_id], false)
              : (data_get($n->payload ?? [], 'project_url') ?: null);

            $html         = $n->html ?? e($n->text);
            $dateClass    = $isRead ? 'text-[#97A3B6]' : 'text-[#486388]';
            $titleClass   = $isRead ? 'text-[#6E8198]' : 'text-[#283544]';
          @endphp

          <div class="py-[10px]">
            <div class="text-[14px] leading-[100%] font-semibold {{ $titleClass }}
                        [&_a]:text-[#599CFF] [&_a]:underline [&_a:hover]:no-underline">
              {!! $html !!}
            </div>

            <div class="mt-1 text-[14px] italic flex items-center gap-2">
              <span class="{{ $dateClass }}">{{ $n->created_at->format('d.m.Y, H:i') }}</span>
              @if($projectName)
                <span class="{{ $dateClass }}">,</span>
                @if($projectUrl)
                  <a href="{{ $projectUrl }}" target="_blank" rel="noopener"
                     class="text-[#599CFF] underline underline-offset-2 hover:no-underline">
                    {{ $projectName }}
                  </a>
                @else
                  <span class="text-[#599CFF] underline underline-offset-2">{{ $projectName }}</span>
                @endif
              @endif
            </div>
          </div>
        @empty
          <div class="py-10 text-center text-slate-500">Пока нет уведомлений</div>
        @endforelse

      </div>
    </div>
  </div>
</div>
