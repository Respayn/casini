@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'suffix' => null,
    'disabled' => false,
    'mask' => null,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $inputValue = $wireModel ? (is_array(old($wireModel)) ? '' : old($wireModel) ?? '') : '';
    $suffix = $suffix ?? '';
    $isPhoneMask = $mask === 'phone';
@endphp

<div {{ $attributes->class(['flex flex-col gap-2'])->only(['class', 'style']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div
        class="relative"
        @if ($isPhoneMask)
            x-data="{
                formatPhone(value) {
                    let digits = String(value || '').replace(/\D/g, '');
                    if (digits.startsWith('8')) {
                        digits = '7' + digits.slice(1);
                    }
                    if (!digits.startsWith('7')) {
                        digits = '7' + digits;
                    }
                    digits = digits.slice(0, 11);

                    let result = '+7';
                    if (digits.length > 1) {
                        result += ' (' + digits.slice(1, 4);
                    }
                    if (digits.length >= 4) {
                        result += ')';
                    }
                    if (digits.length > 4) {
                        result += ' ' + digits.slice(4, 7);
                    }
                    if (digits.length > 7) {
                        result += '-' + digits.slice(7, 9);
                    }
                    if (digits.length > 9) {
                        result += '-' + digits.slice(9, 11);
                    }

                    return result;
                },
                onPhoneInput(event) {
                    const formatted = this.formatPhone(event.target.value);
                    if (event.target.value !== formatted) {
                        event.target.value = formatted;
                    }
                }
            }"
        @endif
    >
        @switch($attributes->get('type'))
            @case('number')
                <input
                    type="text"
                    @if($inputValue !== '') value="{{ $inputValue }}" @endif
                    @class([
                        'min-h-[42px] w-full rounded-[5px] border pe-3',
                        'border-input-border',
                        'border-warning-red' => $wireModel ? $errors->has($wireModel) : false,
                        'ps-[39px]' => isset($icon),
                        'ps-3' => !isset($icon),
                        'disabled:bg-secondary' => $disabled,
                    ])
                    @if ($wireModel) wire:model="{{ $wireModel }}" @endif
                    placeholder="{{ $placeholder }}"
                    onfocus="this.value = this.value.replace(/ /g, '').replace(new RegExp('{{ preg_quote($suffix, '/') }}', 'g'), '');"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(?!^)-/g, '');"
                    onblur="
                    let numValue = parseFloat(this.value.replace(/\s/g, ''));if (!isNaN(numValue)) {
                            this.value = numValue.toLocaleString('ru-RU') + ' {{ $suffix }}';
                        } else {
                            this.value = '';
                        }
                    "
                    @required($required)
                    @disabled($disabled)
                    {{ $attributes->except('class', 'style', 'wire:model', 'type') }}
                />
            @break

            @default
                <input
                    type="{{ $attributes->get('type') ?? 'text' }}"
                    @class([
                        'min-h-[42px] w-full rounded-[5px] border pe-3',
                        'border-input-border',
                        'border-warning-red' => $wireModel ? $errors->has($wireModel) : false,
                        'ps-[39px]' => isset($icon),
                        'ps-3' => !isset($icon),
                        'disabled:bg-secondary' => $disabled,
                    ])
                    placeholder="{{ $placeholder }}"
                    @required($required)
                    @disabled($disabled)
                    @if ($isPhoneMask)
                        x-on:input="onPhoneInput($event)"
                        inputmode="tel"
                        autocomplete="tel"
                    @endif
                    {{ $attributes->except('class', 'style', 'type') }}
                />
        @endswitch
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
