@props([
    'label' => '',
    'options' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'placeholder' => ''
])

@php
    $options = collect($options);
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    // $attributes = $attributes->whereDoesntStartWith('wire:model');
@endphp

<div
    x-data="{
        options: {{ json_encode($options) }},
        open: false,
        selected: @entangle($attributes->wire('model')),
        select(value) {
            $wire.set('{{ $wireModel }}', value);
            this.open = false;
            this.selected = value;
            $dispatch('change');
        },

        getDisplayText() {
            if (!this.open && this.selected) {
                const selectedOption = this.options.find(
                    option => option['{{ $valueKey }}'] == this.selected
                );
                return selectedOption ? selectedOption['{{ $labelKey }}'] : ('{{$placeholder}}' ?? 'Выберите значение');
            }
            return '{{$placeholder}}' ?? 'Выберите значение';
        }
    }"
    {{ $attributes }}
    class="flex flex-col"
>
    <label class="text-primary-text text-sm font-semibold">
        {{ $label }}
    </label>

    <div class="text-input-text relative select-none">
        <div class="group">
            <div
                @class([
                'flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4',
                'border-input-border' => !$errors->has($wireModel),
                'border-warning-red' => $errors->has($wireModel),
                ])
                x-ref="button"
                x-on:click="open = !open"
                x-bind:class="{
                    'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': open,
                    'rounded-[5px]': !open
                }"
            >
                <span x-text="getDisplayText()"></span>
            </div>

            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                <x-icons.arrow
                    class="transition-transform duration-300"
                    x-bind:class="{
                        'rotate-180 group-hover:text-white': open,
                    }"
                />
            </span>
        </div>

        <div
            class="z-1000 w-full rounded-b-[5px] border border-t-0"

            x-cloak
            x-show="open"
            x-anchor="$refs.button"
            x-on:click.outside="open = false"
        >
            @foreach ($options as $option)
                <div
                    class="hover:bg-primary flex min-h-[42px] items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                    x-on:click="select({{ json_encode($option[$valueKey]) }})"
                >
                    {{ $option[$labelKey] }}
                </div>
            @endforeach
        </div>
    </div>
    @error($wireModel)
    <span class="text-warning-red text-[12px]">{{ $message }}</span>
    @enderror
</div>
