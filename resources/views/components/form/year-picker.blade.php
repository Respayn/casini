<div x-data="{ year: new Date().getFullYear() }" x-modelable="year" {{ $attributes->merge(['class' => 'yearpicker-year-selector']) }}>
    <button class="yearpicker-selector-button h-8 w-8" x-on:click="year--">
        <x-icons.accordion-arrow class="rotate-90" />
    </button>
    <div class="yearpicker-year" x-text="year">
    </div>
    <button class="yearpicker-selector-button h-8 w-8" x-on:click="year++">
        <x-icons.accordion-arrow class="rotate-270" />
    </button>
</div>

@once
    <style>
        .yearpicker-year-selector {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
        }

        .yearpicker-selector-button {
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;

            &:hover,
            &.selected {
                background-color: #599CFF;
                color: #FFFFFF;
                border: 0;
                cursor: pointer;
            }
        }

        .yearpicker-year {
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            font-weight: 700;
        }
    </style>
@endonce