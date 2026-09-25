@props([
    'required' => false,
    'tooltip' => '',
    'tooltipPanelMaxWidth' => '16rem',
])

<div class="flex gap-3">
    <label {{ $attributes->class(['text-sm', 'max-w-[250px]', "after:content-['*']" => $required]) }}>
        {{ $slot }}
    </label>
    @if($tooltip)
        <x-overlay.tooltip :panel-max-width="$tooltipPanelMaxWidth">
            {{ $tooltip }}
        </x-overlay.tooltip>
    @endif
</div>
