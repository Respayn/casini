@props([
    'title' => null,
    'content' => null,
    'footer' => null,
])

<div {{ $attributes->class(['border-card-border flex flex-col rounded-[10px] border p-5 gap-2.5']) }}>
    @if ($title)
        <div class="text-primary-text text-lg font-bold">{{ $title }}</div>
    @endif

    @if ($content)
        <div>{{ $content }}</div>
    @endif

    @if ($footer)
        <div class="mt-auto">{{ $footer }}</div>
    @endif
</div>
