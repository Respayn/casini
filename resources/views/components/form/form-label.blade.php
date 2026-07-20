@props([
    'required' => false,
    'tooltip' => '',
])

<div class="flex gap-3">
    <label {{ $attributes->class(['text-sm', 'max-w-[250px]', "after:content-['*']" => $required]) }}>
        {{ $slot }}
    </label>
    @if($tooltip)
        <x-overlay.tooltip>
            {{ $tooltip }}
        </x-overlay.tooltip>
    @endif
</div>
