@props([
    'label' => '',
    'options' => [],
    'labelKey' => 'label',
    'valueKey' => 'value',
    'placeholder' => 'Выберите значение',
    'emptyPlaceholder' => 'Нет доступных вариантов',
    'disabled' => false,
    'searchable' => false,
    'searchPlaceholder' => 'Начните вводить',
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
        searchable: {{ $searchable ? 'true' : 'false' }},
        searchQuery: '',

        labelKey: '{{ $labelKey }}',
        valueKey: '{{ $valueKey }}',
        placeholder: @js($placeholder),
        emptyPlaceholder: @js($emptyPlaceholder),
        searchPlaceholder: @js($searchPlaceholder),

        get hasOptions() {
            return this.options.length > 0;
        },

        get filteredOptions() {
            if (! this.searchable) {
                return this.options;
            }

            const query = this.searchQuery.trim().toLowerCase();
            if (! query) {
                return this.options;
            }

            return this.options.filter((option) =>
                String(option[this.labelKey] ?? '').toLowerCase().includes(query)
            );
        },

        select(value) {
            if (this.disabled || ! this.hasOptions) return;

            this.selected = value;
            this.open = false;
            this.searchQuery = '';

            this.$dispatch('change', { value: value });
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

            if (! this.hasOptions) {
                return this.emptyPlaceholder;
            }

            return this.placeholder;
        },

        toggle() {
            if (this.disabled || ! this.hasOptions) return;
            this.open = ! this.open;
            if (! this.open) {
                this.searchQuery = '';
            } else if (this.searchable) {
                this.$nextTick(() => this.$refs.searchInput?.focus());
            }
        },

        close() {
            this.open = false;
            this.searchQuery = '';
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
                    'border-input-border' => ! $errors->has($wireModel),
                    'border-warning-red' => $errors->has($wireModel),
                ])
                x-ref="button"
                x-on:click="toggle"
                x-bind:class="{
                    'rounded-t-[5px] border-b-0 hover:bg-primary hover:text-white': open && !searchable,
                    'rounded-t-[5px] border-b-0': open && searchable,
                    'rounded-[5px]': !open,
                    'bg-secondary': disabled,
                    'opacity-70': !disabled && !hasOptions
                }"
            >
                @if ($searchable)
                    <input
                        type="text"
                        class="w-full border-0 bg-transparent p-0 text-input-text outline-none placeholder:text-gray-400"
                        x-ref="searchInput"
                        x-show="open"
                        x-cloak
                        x-model="searchQuery"
                        x-bind:placeholder="searchPlaceholder"
                        x-on:click.stop
                        x-on:keydown.escape.stop="close()"
                    />
                    <span
                        x-show="!open"
                        x-text="getDisplayText()"
                        class="overflow-hidden"
                        x-bind:class="{
                            'opacity-50': !hasSelectedValue() && hasOptions,
                            'text-gray-400 italic': !hasOptions
                        }"
                    ></span>
                @else
                    <span
                        x-text="getDisplayText()"
                        class="overflow-hidden"
                        x-bind:class="{
                            'opacity-50': !hasSelectedValue() && hasOptions,
                            'text-gray-400 italic': !hasOptions
                        }"
                    ></span>
                @endif
            </div>

            <template x-if="!disabled && hasOptions">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2">
                    <x-icons.arrow
                        class="transition-transform duration-300"
                        x-bind:class="{
                            'rotate-180 group-hover:text-white': open && !searchable,
                            'rotate-180': open && searchable,
                        }"
                    />
                </span>
            </template>
        </div>

        <div
            class="z-1000 border-input-border max-h-52 w-full overflow-y-auto rounded-b-[5px] border border-t-0 bg-white"
            x-cloak
            x-show="open && hasOptions"
            x-anchor.no-style="$refs.buttonContainer"
            x-bind:style="{ position: 'absolute', top: $anchor.y + 'px' }"
            x-on:click.outside="close()"
        >
            <div
                class="flex min-h-[42px] items-center bg-white px-4 text-sm italic text-gray-400"
                x-show="searchable && filteredOptions.length === 0"
            >Ничего не найдено</div>
            <template
                x-for="option in filteredOptions"
                :key="option['{{ $valueKey }}']"
            >
                <div
                    class="hover:bg-primary flex min-h-[42px] cursor-pointer items-center bg-white pe-10 ps-4 last:rounded-b-[5px] hover:text-white"
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
