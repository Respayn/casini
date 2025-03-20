@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'suffix' => null,
    'disabled' => false,
])

@php
    $wireModel = $attributes->get('wire:model');
    $inputValue = is_array(old($wireModel)) ? '' : old($wireModel) ?? '';
    $suffix = $suffix ?? '';
@endphp

<div {{ $attributes->class(['flex flex-col gap-2'])->except('wire:model') }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <div class="flex flex-col justify-start">
            @switch($attributes->get('type'))
                @case('number')
                    <input
                        type="text"
                        @class([
                            'min-h-[42px] w-full rounded-[5px] border pe-3',
                            'border-input-border',
                            'border-warning-red' => $wireModel ? $errors->has($wireModel) : false,
                            'ps-[39px]' => isset($icon),
                            'ps-3' => !isset($icon),
                            'disabled:bg-secondary' => $disabled,
                        ])
                        wire:model="{{ $wireModel }}"
                        placeholder="{{ $placeholder }}"
                        value="{{ $inputValue }}"
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
                    {{ $attributes->except('wire:model', 'type') }}
                    @break
                @default
                    <input
                        type="text"
                        @class([
                            'min-h-[42px] w-full rounded-[5px] border pe-3',
                            'border-input-border',
                            'border-warning-red' => $wireModel ? $errors->has($wireModel) : false,
                            'ps-[39px]' => isset($icon),
                            'ps-3' => !isset($icon),
                            'disabled:bg-secondary' => $disabled,
                        ])
                        wire:model="{{ $wireModel }}"
                        placeholder="{{ $placeholder }}"
                    @required($required)
                    @disabled($disabled)
                    {{ $attributes->except('wire:model', 'type') }}
            @endswitch
            @error(!empty($wireModel))
            <span class="text-warning-red text-[12px]">{{ $message }}</span>
            @enderror
        </div>
        @if ($icon)
            <span class="absolute left-[13px] top-1/2 -translate-y-1/2">
                <x-icons.search />
            </span>
        @endif
    </div>
</div>
