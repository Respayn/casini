@props([
    'label' => null,
    'placeholder' => '',
    'icon' => null,
    'required' => false,
    'suffix' => null,
])

@php
    $wireModel = $attributes->get('wire:model');
    $inputValue = is_array(old($wireModel)) ? '' : old($wireModel) ?? '';
    $suffix = $suffix ?? '';
@endphp

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    @if ($label)
        <label class="text-primary-text text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <div class="flex flex-col justify-start">
            @switch($attributes['type'])
                @case('number')
                    <input
                        type="text"
                        @class([
                            'min-h-[42px] w-full rounded-[5px] border pe-3 disabled:bg-secondary',
                            'border-input-border' => !$errors->has($wireModel),
                            'border-warning-red' => $errors->has($wireModel),
                            'ps-[39px]' => isset($icon),
                            'ps-3' => !isset($icon),
                        ])
                        {{ $attributes->wire('model') }}
                        {{ $attributes->whereStartsWith('x-') }}
                        placeholder="{{ $placeholder }}"
                        value="{{ $inputValue }}"
                        onfocus="this.value = this.value.replace(/ /g, '').replace(new RegExp('{{ preg_quote($suffix, '/') }}', 'g'), '');"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                        onblur="
                        let numValue = parseFloat(this.value.replace(/\s/g, ''));if (!isNaN(numValue)) {
                                this.value = numValue.toLocaleString('ru-RU') + ' {{ $suffix }}';
                            } else {
                                this.value = '';
                            }
                        "
                        @required($required)
                    />
                    @break
                @default
                    <input
                        type="text"
                        @class([
                            'min-h-[42px] w-full rounded-[5px] border pe-3 disabled:bg-secondary',
                            'border-input-border' => !$errors->has($wireModel),
                            'border-warning-red' => $errors->has($wireModel),
                            'ps-[39px]' => isset($icon),
                            'ps-3' => !isset($icon),
                        ])
                        {{ $attributes->wire('model') }}
                        {{ $attributes->whereStartsWith('x-') }}
                        placeholder="{{ $placeholder }}"
                        @required($required)
                    />
            @endswitch
            @error($wireModel)
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
