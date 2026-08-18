@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'suffix' => null,
    'disabled' => false,
    'mask' => null,
    'allowNegative' => false,
])

@php
    $wireModelBag = $attributes->whereStartsWith('wire:model');
    $wireModel = $wireModelBag->first();
    $inputValue = $wireModel ? (is_array(old($wireModel)) ? '' : old($wireModel) ?? '') : '';
    $suffix = $suffix ?? '';
    $hasSuffixAddon = $suffix !== '';
    $isPhoneMask = $mask === 'phone';
    $isNumber = $attributes->get('type') === 'number';
    $hasError = $wireModel && $errors->has($wireModel);
    $allowNegative = filter_var($allowNegative, FILTER_VALIDATE_BOOLEAN);
    $otherAttributes = $attributes
        ->whereDoesntStartWith('wire:model')
        ->except(['class', 'style', 'type']);
@endphp

<div {{ $attributes->class(['flex flex-col gap-2'])->only(['class', 'style']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        @if ($isNumber && $hasSuffixAddon)
            <div
                x-data="{
                    editing: false,
                    displayValue: '',
                    allowNegative: {{ $allowNegative ? 'true' : 'false' }},

                    formatForDisplay(raw) {
                        const cleaned = String(raw ?? '').replace(/[\s\u00A0\u202F]/g, '');
                        if (cleaned === '' || cleaned === '-') return '';
                        const num = parseFloat(cleaned);
                        if (Number.isNaN(num)) return '';
                        return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(num);
                    },

                    syncDisplay() {
                        this.displayValue = this.formatForDisplay(this.$refs.numInput.value);
                    },

                    onFocus() {
                        this.editing = true;
                        const input = this.$refs.numInput;
                        input.value = String(input.value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                    },

                    onBlur() {
                        const input = this.$refs.numInput;
                        const raw = String(input.value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                        if (raw === '' || raw === '-') {
                            input.value = '';
                        } else {
                            const num = parseFloat(raw);
                            input.value = Number.isNaN(num) ? '' : String(num);
                        }
                        this.editing = false;
                        this.$nextTick(() => this.syncDisplay());
                    },

                    onInput() {
                        const input = this.$refs.numInput;
                        if (this.allowNegative) {
                            input.value = input.value.replace(/[^\d-]/g, '').replace(/(?!^)-/g, '');
                        } else {
                            input.value = input.value.replace(/\D/g, '');
                        }
                    },

                    init() {
                        this.$nextTick(() => this.syncDisplay());
                        document.addEventListener('livewire:update', () => {
                            this.$nextTick(() => { if (!this.editing) this.syncDisplay(); });
                        });
                    },
                }"
                @class([
                    'flex min-h-[42px] w-full overflow-hidden rounded-[5px] border',
                    'border-input-border' => ! $hasError,
                    'border-warning-red' => $hasError,
                    'bg-secondary' => $disabled,
                ])
            >
                <div class="relative w-full">
                    <input
                        x-ref="numInput"
                        type="text"
                        @if ($inputValue !== '') value="{{ $inputValue }}" @endif
                        class="text-input-text min-h-[42px] w-full border-0 bg-transparent px-3 outline-none disabled:cursor-not-allowed"
                        placeholder="{{ $placeholder }}"
                        @required($required)
                        @disabled($disabled)
                        {{ $wireModelBag }}
                        {{ $otherAttributes }}
                        x-on:focus="onFocus()"
                        x-on:blur="onBlur()"
                        x-on:input="onInput()"
                        x-bind:class="{ 'opacity-0': !editing && displayValue !== '' }"
                    />
                    <span
                        x-show="!editing && displayValue !== ''"
                        x-text="displayValue"
                        x-on:click="$refs.numInput.focus()"
                        class="text-input-text pointer-events-auto absolute inset-0 flex items-center px-3 cursor-text"
                    ></span>
                </div>
                <span
                    class="bg-secondary text-caption-text pointer-events-none flex shrink-0 items-center border-l border-input-border px-3 text-sm font-semibold"
                    aria-hidden="true"
                >{{ $suffix }}</span>
            </div>
        @elseif ($isNumber)
            <div
                x-data="{
                    editing: false,
                    displayValue: '',
                    allowNegative: {{ $allowNegative ? 'true' : 'false' }},

                    formatForDisplay(raw) {
                        const cleaned = String(raw ?? '').replace(/[\s\u00A0\u202F]/g, '');
                        if (cleaned === '' || cleaned === '-') return '';
                        const num = parseFloat(cleaned);
                        if (Number.isNaN(num)) return '';
                        return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 2 }).format(num);
                    },

                    syncDisplay() {
                        this.displayValue = this.formatForDisplay(this.$refs.numInput.value);
                    },

                    onFocus() {
                        this.editing = true;
                        const input = this.$refs.numInput;
                        input.value = String(input.value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                    },

                    onBlur() {
                        const input = this.$refs.numInput;
                        const raw = String(input.value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                        if (raw === '' || raw === '-') {
                            input.value = '';
                        } else {
                            const num = parseFloat(raw);
                            input.value = Number.isNaN(num) ? '' : String(num);
                        }
                        this.editing = false;
                        this.$nextTick(() => this.syncDisplay());
                    },

                    onInput() {
                        const input = this.$refs.numInput;
                        if (this.allowNegative) {
                            input.value = input.value.replace(/[^\d-]/g, '').replace(/(?!^)-/g, '');
                        } else {
                            input.value = input.value.replace(/\D/g, '');
                        }
                    },

                    init() {
                        this.$nextTick(() => this.syncDisplay());
                        document.addEventListener('livewire:update', () => {
                            this.$nextTick(() => { if (!this.editing) this.syncDisplay(); });
                        });
                    },
                }"
                class="relative"
            >
                <input
                    x-ref="numInput"
                    type="text"
                    @if ($inputValue !== '') value="{{ $inputValue }}" @endif
                    @class([
                        'min-h-[42px] w-full rounded-[5px] border pe-3',
                        'border-input-border',
                        'border-warning-red' => $hasError,
                        'ps-[39px]' => isset($icon),
                        'ps-3' => ! isset($icon),
                        'disabled:bg-secondary' => $disabled,
                    ])
                    placeholder="{{ $placeholder }}"
                    @required($required)
                    @disabled($disabled)
                    {{ $wireModelBag }}
                    {{ $otherAttributes }}
                    x-on:focus="onFocus()"
                    x-on:blur="onBlur()"
                    x-on:input="onInput()"
                    x-bind:class="{ 'opacity-0': !editing && displayValue !== '' }"
                />
                <span
                    x-show="!editing && displayValue !== ''"
                    x-text="displayValue"
                    x-on:click="$refs.numInput.focus()"
                    @class([
                        'pointer-events-auto absolute inset-0 flex items-center cursor-text text-input-text',
                        'ps-[39px]' => isset($icon),
                        'ps-3' => ! isset($icon),
                    ])
                ></span>
            </div>
        @else
            <input
                type="{{ $attributes->get('type') ?? 'text' }}"
                @class([
                    'min-h-[42px] w-full rounded-[5px] border pe-3',
                    'border-input-border',
                    'border-warning-red' => $hasError,
                    'ps-[39px]' => isset($icon),
                    'ps-3' => ! isset($icon),
                    'disabled:bg-secondary' => $disabled,
                ])
                placeholder="{{ $placeholder }}"
                @required($required)
                @disabled($disabled)
                @if ($isPhoneMask)
                    x-mask="+7 (999) 999-99-99"
                    inputmode="tel"
                    autocomplete="tel"
                @endif
                {{ $attributes->except(['class', 'style', 'type']) }}
            />
        @endif
        @if ($icon)
            <span class="pointer-events-none absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-dynamic-component :component="$icon" class="h-5 w-5 text-gray-400" />
            </span>
        @endif
    </div>
    @if ($wireModel)
        @error($wireModel)
            <span class="text-warning-red text-[12px]">{{ $message }}</span>
        @enderror
    @endif
</div>
