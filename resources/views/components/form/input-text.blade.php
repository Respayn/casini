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
                @class([
                    'flex min-h-[42px] w-full overflow-hidden rounded-[5px] border',
                    'border-input-border' => ! $hasError,
                    'border-warning-red' => $hasError,
                    'bg-secondary' => $disabled,
                ])
            >
                <input
                    type="text"
                    @if ($inputValue !== '') value="{{ $inputValue }}" @endif
                    class="text-input-text min-h-[42px] w-full border-0 bg-transparent px-3 outline-none disabled:cursor-not-allowed"
                    placeholder="{{ $placeholder }}"
                    @required($required)
                    @disabled($disabled)
                    {{ $wireModelBag }}
                    {{ $otherAttributes }}
                    x-data
                    x-init="
                        const input = $el;
                        const allowNegative = {{ $allowNegative ? 'true' : 'false' }};
                        const stripSpaces = (value) => String(value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                        const format = () => {
                            if (document.activeElement === input) {
                                return;
                            }
                            const raw = stripSpaces(input.value);
                            if (raw === '' || raw === '-') {
                                return;
                            }
                            const number = parseFloat(raw);
                            if (!Number.isNaN(number)) {
                                input.value = number.toLocaleString('ru-RU');
                            }
                        };
                        const unformat = () => {
                            input.value = stripSpaces(input.value);
                        };
                        const sanitize = () => {
                            if (allowNegative) {
                                input.value = input.value.replace(/[^\d-]/g, '').replace(/(?!^)-/g, '');
                            } else {
                                input.value = input.value.replace(/\D/g, '');
                            }
                        };
                        const prepareForSync = () => {
                            const raw = stripSpaces(input.value);
                            if (raw === '' || raw === '-') {
                                input.value = '';
                            } else {
                                const number = parseFloat(raw);
                                input.value = Number.isNaN(number) ? '' : String(number);
                            }
                        };

                        format();
                        input.addEventListener('focus', unformat);
                        input.addEventListener('input', sanitize);
                        input.addEventListener('blur', prepareForSync, true);
                        input.addEventListener('blur', () => {
                            // Reformat only this field after Livewire blur sync
                            queueMicrotask(format);
                        });

                        if (window.Livewire) {
                            Livewire.hook('morph.updated', ({ el }) => {
                                if (el === input || (el.contains && el.contains(input))) {
                                    queueMicrotask(format);
                                }
                            });
                        }
                    "
                />
                <span
                    class="bg-secondary text-caption-text pointer-events-none flex shrink-0 items-center border-l border-input-border px-3 text-sm font-semibold"
                    aria-hidden="true"
                >{{ $suffix }}</span>
            </div>
        @elseif ($isNumber)
            <input
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
                x-data
                x-init="
                    const input = $el;
                    const allowNegative = {{ $allowNegative ? 'true' : 'false' }};
                    const stripSpaces = (value) => String(value ?? '').replace(/[\s\u00A0\u202F]/g, '');
                    const format = () => {
                        if (document.activeElement === input) {
                            return;
                        }
                        const raw = stripSpaces(input.value);
                        if (raw === '' || raw === '-') {
                            return;
                        }
                        const number = parseFloat(raw);
                        if (!Number.isNaN(number)) {
                            input.value = number.toLocaleString('ru-RU');
                        }
                    };
                    const unformat = () => {
                        input.value = stripSpaces(input.value);
                    };
                    const sanitize = () => {
                        if (allowNegative) {
                            input.value = input.value.replace(/[^\d-]/g, '').replace(/(?!^)-/g, '');
                        } else {
                            input.value = input.value.replace(/\D/g, '');
                        }
                    };
                    const prepareForSync = () => {
                        const raw = stripSpaces(input.value);
                        if (raw === '' || raw === '-') {
                            input.value = '';
                            return;
                        }
                        const number = parseFloat(raw);
                        input.value = Number.isNaN(number) ? '' : String(number);
                    };

                    format();
                    input.addEventListener('focus', unformat);
                    input.addEventListener('input', sanitize);
                    input.addEventListener('blur', prepareForSync, true);
                    input.addEventListener('blur', () => {
                        queueMicrotask(format);
                    });

                    if (window.Livewire) {
                        Livewire.hook('morph.updated', ({ el }) => {
                            if (el === input || (el.contains && el.contains(input))) {
                                queueMicrotask(format);
                            }
                        });
                    }
                "
            />
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
