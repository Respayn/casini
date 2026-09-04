@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'suffix' => null,
    'disabled' => false,
    'allowNegative' => false,
])

@php
    $wireModelBag = $attributes->whereStartsWith('wire:model');
    $wireModel = $wireModelBag->first();
    $inputValue = $wireModel ? (is_array(old($wireModel)) ? '' : old($wireModel) ?? '') : '';
    $suffix = $suffix ?? '';
    $hasSuffixAddon = $suffix !== '';
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
    <div
        class="relative"
        x-data="{
            allowNegative: {{ $allowNegative ? 'true' : 'false' }},

            stripSpaces(value) {
                return String(value ?? '').replace(/[\s\u00A0\u202F]/g, '');
            },

            format() {
                const input = this.$refs.numInput;
                if (!input || document.activeElement === input) {
                    return;
                }
                const raw = this.stripSpaces(input.value);
                if (raw === '' || raw === '-') {
                    return;
                }
                const number = parseFloat(raw);
                if (!Number.isNaN(number)) {
                    input.value = number.toLocaleString('ru-RU');
                }
            },

            unformat() {
                const input = this.$refs.numInput;
                if (!input) {
                    return;
                }
                input.value = this.stripSpaces(input.value);
            },

            sanitize() {
                const input = this.$refs.numInput;
                if (!input) {
                    return;
                }
                if (this.allowNegative) {
                    input.value = input.value.replace(/[^\d-]/g, '').replace(/(?!^)-/g, '');
                } else {
                    input.value = input.value.replace(/\D/g, '');
                }
            },

            prepareForSync() {
                const input = this.$refs.numInput;
                if (!input) {
                    return;
                }
                const raw = this.stripSpaces(input.value);
                if (raw === '' || raw === '-') {
                    input.value = '';
                    return;
                }
                const number = parseFloat(raw);
                input.value = Number.isNaN(number) ? '' : String(number);
            },

            init() {
                this.$nextTick(() => this.format());

                if (window.Livewire) {
                    Livewire.hook('morph.updated', ({ el }) => {
                        const input = this.$refs.numInput;
                        if (!input) {
                            return;
                        }
                        if (el === input || (el.contains && el.contains(input))) {
                            queueMicrotask(() => this.format());
                        }
                    });
                }
            },
        }"
    >
        @if ($hasSuffixAddon)
            <div
                @class([
                    'flex min-h-[42px] w-full overflow-hidden rounded-[5px] border',
                    'border-input-border' => ! $hasError,
                    'border-warning-red' => $hasError,
                    'bg-secondary' => $disabled,
                ])
            >
                <input
                    x-ref="numInput"
                    type="text"
                    inputmode="decimal"
                    @if ($inputValue !== '') value="{{ $inputValue }}" @endif
                    class="text-input-text min-h-[42px] w-full border-0 bg-transparent px-3 outline-none disabled:cursor-not-allowed"
                    placeholder="{{ $placeholder }}"
                    @required($required)
                    @disabled($disabled)
                    {{ $wireModelBag }}
                    {{ $otherAttributes }}
                    x-on:focus="unformat()"
                    x-on:input="sanitize()"
                    x-on:blur.capture="prepareForSync()"
                    x-on:blur="queueMicrotask(() => format())"
                />
                <span
                    class="bg-secondary text-caption-text pointer-events-none flex shrink-0 items-center border-l border-input-border px-3 text-sm font-semibold"
                    aria-hidden="true"
                >{{ $suffix }}</span>
            </div>
        @else
            <input
                x-ref="numInput"
                type="text"
                inputmode="decimal"
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
                x-on:focus="unformat()"
                x-on:input="sanitize()"
                x-on:blur.capture="prepareForSync()"
                x-on:blur="queueMicrotask(() => format())"
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
