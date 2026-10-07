@props([
    'placeholder' => 'example.com',
    'disabled' => false,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
@endphp

<div {{ $attributes->class(['flex flex-col gap-2'])->only(['class', 'style']) }}>
    <div
        x-data="{
            protocol: 'https://',
            host: '',
            disabled: {{ $isDisabled ? 'true' : 'false' }},
            full: @entangle($wireModel).live,

            init() {
                this.fromFull(this.full || '');
                this.$watch('full', (value) => {
                    const current = this.host === '' ? '' : (this.protocol + this.host);
                    if ((value || '') !== current) {
                        this.fromFull(value || '');
                    }
                });
            },

            fromFull(url) {
                url = String(url || '').trim();

                if (/^https:\/\//i.test(url)) {
                    this.protocol = 'https://';
                    this.host = url.replace(/^https:\/\//i, '');
                } else if (/^http:\/\//i.test(url)) {
                    this.protocol = 'http://';
                    this.host = url.replace(/^http:\/\//i, '');
                } else {
                    this.host = url;
                }
            },

            toggleProtocol() {
                if (this.disabled) {
                    return;
                }

                this.protocol = this.protocol === 'https://' ? 'http://' : 'https://';
                this.push();
            },

            onHostInput(event) {
                let value = event.target.value;

                if (/^https:\/\//i.test(value)) {
                    this.protocol = 'https://';
                    this.host = value.replace(/^https:\/\//i, '');
                    event.target.value = this.host;
                } else if (/^http:\/\//i.test(value)) {
                    this.protocol = 'http://';
                    this.host = value.replace(/^http:\/\//i, '');
                    event.target.value = this.host;
                } else {
                    this.host = value;
                }

                this.push();
            },

            push() {
                this.full = this.host === '' ? '' : (this.protocol + this.host);
                // Чтобы родительский x-on:change.capture увидел переключение http/https
                this.$el.dispatchEvent(new Event('change', { bubbles: true }));
            },
        }"
        @class([
            'flex min-h-[42px] w-full overflow-hidden rounded-[5px] border',
            'border-input-border' => ! ($wireModel && $errors->has($wireModel)),
            'border-warning-red' => $wireModel && $errors->has($wireModel),
            'bg-secondary' => $isDisabled,
        ])
    >
        <button
            type="button"
            class="bg-secondary text-caption-text hover:text-primary-text shrink-0 cursor-pointer border-r border-input-border px-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:bg-transparent disabled:text-input-text disabled:opacity-70"
            x-text="protocol"
            x-on:click="toggleProtocol"
            @disabled($isDisabled)
            tabindex="{{ $isDisabled ? '-1' : '0' }}"
            title="Нажмите, чтобы переключить http:// и https://"
        ></button>
        <input
            type="text"
            class="text-input-text min-h-[42px] w-full border-0 bg-transparent px-3 outline-none disabled:cursor-not-allowed"
            placeholder="{{ $placeholder }}"
            x-bind:value="host"
            x-on:input="onHostInput($event)"
            @disabled($isDisabled)
            inputmode="url"
            autocomplete="url"
            {{ $attributes->whereDoesntStartWith('wire:model')->except(['class', 'style']) }}
        />
    </div>
    @if ($wireModel)
        @error($wireModel)
            <span class="text-warning-red text-[12px]">{{ $message }}</span>
        @enderror
    @endif
</div>
