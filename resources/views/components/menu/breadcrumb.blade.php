@props([
    'items' => [],
])

<div class="flex items-center gap-2">
    @foreach ($items as $item)
        <div>
            <a
                class="text-sm font-bold text-primary"
                href="{{ $item['link'] }}"
            >{{ $item['label'] }}</a>
        </div>
        @unless ($loop->last)
            <x-icons.breadcrumb-arrow />
        @endunless
    @endforeach
</div>
