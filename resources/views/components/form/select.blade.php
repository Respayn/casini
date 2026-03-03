@props([
    'label' => '',
    'options' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'placeholder' => 'Выберите значение',
    'emptyPlaceholder' => 'Нет доступных вариантов',
    'disabled' => false,
])

@php
    $wireModel = $attributes->wire('model')->value();
@endphp

<div
    class="flex w-full flex-col gap-2"
    x-data="{
        open: false,
        options: {{ json_encode($options) }},
        selected: '',
        disabled: {{ $disabled ? 'true' : 'false' }},

        labelKey: '{{ $labelKey }}',
        valueKey: '{{ $valueKey }}',
        placeholder: '{{ $placeholder }}',
        emptyPlaceholder: '{{ $emptyPlaceholder }}',

        get hasOptions() {
            return this.options.length > 0;
        },

        select(value) {
            if (this.disabled || !this.hasOptions) return;
            
            this.selected = value;
            this.open = false;

            this.$dispatch('change', { value: value });
        },
    
        getDisplayText() {
            if (this.selected) {
                const option = this.options.find(o => o[this.valueKey] == this.selected);
    
                if (option) {
                    return option[this.labelKey];
                }
            }

            if (!this.hasOptions) {
                return this.emptyPlaceholder;
            }
    
            return this.placeholder;
        },

        toggle() {
            if (this.disabled || !this.hasOptions) return;
            this.open = !this.open;
        }
    }"
    x-modelable="selected"
    {{ $attributes }}
>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">
            {{ $label }}
        </label>
    @endif

    <div class="text-input-text relative select-none">
        <div class="group" x-ref="buttonContainer">
            <div
                @class([
                    'flex min-h-[42px] w-full items-center rounded-[5px] border pe-10 ps-4',
                    'border-input-border' => !$errors->has($wireModel),
                    'border-warning-red' => $errors->has($wireModel),
                ])
                x-ref="button"
                x-on:click="toggle"
                x-bind:class="{
                    'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': open,
                    'rounded-[5px]': !open,
                    'bg-secondary': disabled,
                    'opacity-70': !disabled && !hasOptions
                }"
            >
                <span
                    x-text="getDisplayText()"
                    x-bind:class="{ 
                        'opacity-50': !selected && hasOptions,
                        'text-gray-400 italic': !hasOptions
                    }"
                ></span>
            </div>

            <template x-if="!disabled && hasOptions">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                    <x-icons.arrow
                        class="transition-transform duration-300"
                        x-bind:class="{
                            'rotate-180 group-hover:text-white': open,
                        }"
                    />
                </span>
            </template>
        </div>

        <div
            class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0"
            x-cloak
            x-show="open && hasOptions"
            x-anchor.no-style="$refs.buttonContainer"
            x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
            x-on:click.outside="open = false"
        >
            <template
                x-for="option in options"
                :key="option['{{ $valueKey }}']"
            >
                <div
                    class="hover:bg-primary flex min-h-[42px] items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
                    x-on:click="select( option['{{ $valueKey }}'])"
                    x-text="option['{{ $labelKey }}']"
                >
                </div>
            </template>
        </div>
    </div>
    @error($wireModel)
        <span class="text-warning-red text-[12px]">{{ $message }}</span>
    @enderror
</div>
