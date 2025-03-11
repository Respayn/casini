@props([
    'required' => false,
    'tooltip' => '',
])

<div class="flex gap-3">
    <label {{ $attributes->class(['text-sm', "after:content-['*']" => $required]) }}>
        {{ $slot }}
    </label>
    @if($tooltip)
        <x-overlay.tooltip>
            {{ $tooltip }}
        </x-overlay.tooltip>
    @endif
</div>
