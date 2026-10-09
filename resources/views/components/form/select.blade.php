@props([
    'label' => '',
    'options' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'placeholder' => 'Выберите значение',
    'emptyPlaceholder' => 'Нет доступных вариантов',
    'disabled' => false,
    'searchable' => false,
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
        search: '',

        labelKey: '{{ $labelKey }}',
        valueKey: '{{ $valueKey }}',
        placeholder: '{{ $placeholder }}',
        emptyPlaceholder: '{{ $emptyPlaceholder }}',

        get hasOptions() {
            return this.options.length > 0;
        },

        get filteredOptions() {
            const query = this.search.trim().toLowerCase();
            if (!query) return this.options;

            return this.options.filter(o => String(o[this.labelKey] ?? '').toLowerCase().includes(query));
        },

        select(value) {
            if (this.disabled || !this.hasOptions) return;

            this.selected = value;
            this.close();

            this.$dispatch('change', { value: value });
        },

        close() {
            this.open = false;
            this.search = '';
        },

        hasSelectedValue() {
            // false/0 — валидный выбор (напр. «Неактивен»), пусто только null/undefined/''
            return this.selected !== null && this.selected !== undefined && this.selected !== '';
        },

        getDisplayText() {
            if (this.hasSelectedValue()) {
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
            if (this.open) {
                this.close();
                return;
            }
            this.open = true;
            this.$nextTick(() => this.$refs.search?.focus());
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
                @if ($searchable)
                    <input
                        type="text"
                        class="w-full border-0 bg-transparent p-0 outline-none"
                        placeholder="Начните вводить"
                        x-ref="search"
                        x-model="search"
                        x-show="open"
                        x-on:click.stop
                        x-on:keydown.escape.stop="close()"
                        x-on:keydown.enter.prevent="filteredOptions.length && select(filteredOptions[0][valueKey])"
                    />
                @endif
                <span
                    x-text="getDisplayText()"
                    @if ($searchable) x-show="!open" @endif
                    class="overflow-hidden"
                    x-bind:class="{
                        'opacity-50': !hasSelectedValue() && hasOptions,
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
            x-on:click.outside="close()"
        >
            @if ($searchable)
                <div
                    class="flex min-h-[42px] items-center bg-white px-4 text-sm italic text-gray-400"
                    x-show="filteredOptions.length === 0"
                >Ничего не найдено</div>
            @endif
            <template
                x-for="option in filteredOptions"
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
