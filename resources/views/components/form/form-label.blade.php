@props([
    'required' => false,
    'tooltip' => '',
])

<div class="flex gap-3">
    <label {{ $attributes->class(['text-sm', "after:content-['*']" => $required]) }}>
        {{ $slot }}
    </label>
    @if($tooltip)
        <x-form.tooltip>
            {{ $tooltip }}
        </x-form.tooltip>
    @endif
</div>
