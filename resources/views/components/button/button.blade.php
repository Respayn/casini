@props([
    'icon' => null,
    'variant' => null,
    'label' => '',
    'rounded' => false,
    'disabled' => false,
])

@if ($rounded)
    <button class="border-input-border rounded-full border p-[10px]">
        @if ($icon)
            <x-dynamic-component
                class="text-secondary-text"
                :component="$icon"
            />
        @endif
        {{ $label }}
    </button>
@else
    @switch ($variant)
        @case('link')
            <div
                class="text-primary-text hover:text-primary group inline-flex cursor-pointer items-center"
                {{ $attributes->whereStartsWith('wire:click') }}
                {{ $attributes->whereStartsWith('x-on:') }}
            >
                @if ($icon)
                    <x-dynamic-component
                        class="mr-4"
                        :component="$icon"
                    />
                @endif
                <span class="font-semibold group-hover:underline">{{ $label }}</span>
            </div>
        @break

        @case('primary')
            <button
                {{ $attributes->whereStartsWith('wire:click') }}
                {{ $attributes->whereStartsWith('x-on:') }}
                @class([
                    'inline-flex items-center justify-center hover:not-disabled:bg-transparent bg-primary border-primary disabled:text-default-button-disabled hover:not-disabled:text-primary not-disabled:cursor-pointer disabled:bg-secondary rounded-lg border text-white disabled:cursor-not-allowed disabled:border-0',
                    'w-10 h-10' => empty($label),
                    'px-3.5 py-2.5' => !empty($label),
                ])
                @disabled($disabled)
            >
                @if ($icon)
                    <x-dynamic-component
                        @class([
                            'mr-4' => !empty($label),
                        ])
                        :component="$icon"
                    />
                @endif
                {{ $label }}
            </button>
        @break

        @case('ghost')
            <button
                @class([
                    'inline-flex items-center justify-center text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white not-disabled:cursor-pointer disabled:bg-secondary rounded-lg disabled:cursor-not-allowed',
                    'w-10 h-10' => empty($label),
                    'px-3.5 py-2.5' => !empty($label),
                ])
                {{ $attributes }}
                @disabled($disabled)
            >
                @if ($icon)
                    <x-dynamic-component
                        @class([
                            'mr-4' => !empty($label),
                        ])
                        :component="$icon"
                    />
                @endif
                {{ $label }}
            </button>
        @break

        @default
            <button
                @class([
                    'inline-flex items-center justify-center border-default-button text-default-button hover:not-disabled:bg-default-button disabled:text-default-button-disabled hover:not-disabled:text-white not-disabled:cursor-pointer disabled:bg-secondary rounded-lg border disabled:cursor-not-allowed disabled:border-0',
                    'w-10 h-10' => empty($label),
                    'px-3.5 py-2.5' => !empty($label),
                ])
                {{ $attributes->whereStartsWith('wire:click') }}
                {{ $attributes->whereStartsWith('x-on:') }}
                @disabled($disabled)
            >
                @if ($icon)
                    <x-dynamic-component
                        @class([
                            'mr-4' => !empty($label),
                        ])
                        :component="$icon"
                    />
                @endif
                {{ $label }}
            </button>
    @endswitch
@endif
