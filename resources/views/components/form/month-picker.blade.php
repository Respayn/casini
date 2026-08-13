@props([
    'borderColor' => '#C4D0E0',
    'placeholder' => 'Выберите месяц',
    'min' => null,
    'max' => null,
])

@php
    $max ??= now()->toDateString();
@endphp

{{-- TODO: объединить этот компонент с компонентом date-picker. Сделать по аналогии с компонентом из библиотеки PrimeVue
--}}
<div class="monthpicker-wrap">
    <span
        class="monthpicker-bounds"
        hidden
        data-min="{{ $min }}"
        data-max="{{ $max }}"
    ></span>
<div
    class="monthpicker"
    data-min="{{ $min }}"
    data-max="{{ $max }}"
    x-data="monthpicker({ placeholder: @js($placeholder), min: @js($min), max: @js($max) })"
    x-modelable="value"
    {{ $attributes }}
>
    {{-- Trigger --}}
    <button type="button" class="monthpicker-trigger" x-ref="trigger" x-on:click="toggle">
        <span class="monthpicker-trigger__icon">
            <x-icons.calendar />
        </span>
        <span
            class="monthpicker-trigger__label"
            x-bind:class="{ 'monthpicker-trigger__label--placeholder': ! hasValue() }"
            x-bind:title="displayValue"
            x-text="displayValue"
        ></span>
    </button>

    {{-- Dropdown --}}
    <div class="monthpicker-dropdown" x-show="isOpen" x-transition x-cloak x-anchor="$refs.trigger"
        x-on:click.outside="close">
        {{-- Year navigation --}}
        <nav class="monthpicker-year-nav">
            <button
                type="button"
                class="monthpicker-btn monthpicker-btn--square"
                x-bind:disabled="! canGoPrevYear()"
                x-on:click="prevYear"
            >
                <x-icons.accordion-arrow class="rotate-90" />
            </button>

            <div class="monthpicker-year-nav__label" x-text="year"></div>

            <button
                type="button"
                class="monthpicker-btn monthpicker-btn--square"
                x-bind:disabled="! canGoNextYear()"
                x-on:click="nextYear"
            >
                <x-icons.accordion-arrow class="rotate-270" />
            </button>
        </nav>

        {{-- Month grid --}}
        <div class="monthpicker-grid">
            <template x-for="(monthData, index) in monthMap">
                <button
                    type="button"
                    class="monthpicker-btn monthpicker-btn--month"
                    x-bind:disabled="isMonthDisabled(index)"
                    x-bind:class="{ 'selected': monthSelected(index) }"
                    x-text="monthData.short"
                    x-on:click="selectMonth(index)"
                ></button>
            </template>
        </div>
    </div>
</div>
</div>

@once
    @script
    <script>
        Alpine.data('monthpicker', (config = {}) => ({
            placeholder: config.placeholder || 'Выберите месяц',
            min: config.min || null,
            max: config.max || null,
            value: null,
            year: new Date().getFullYear(),
            month: new Date().getMonth(),
            isOpen: false,
            monthMap: {
                0: {
                    short: 'Янв.',
                    full: 'Январь'
                },
                1: {
                    short: 'Фев.',
                    full: 'Февраль'
                },
                2: {
                    short: 'Мар.',
                    full: 'Март'
                },
                3: {
                    short: 'Апр.',
                    full: 'Апрель'
                },
                4: {
                    short: 'Май',
                    full: 'Май'
                },
                5: {
                    short: 'Июн.',
                    full: 'Июнь'
                },
                6: {
                    short: 'Июл.',
                    full: 'Июль'
                },
                7: {
                    short: 'Авг.',
                    full: 'Август'
                },
                8: {
                    short: 'Сент.',
                    full: 'Сентябрь'
                },
                9: {
                    short: 'Окт.',
                    full: 'Октябрь'
                },
                10: {
                    short: 'Нояб.',
                    full: 'Ноябрь'
                },
                11: {
                    short: 'Дек.',
                    full: 'Декабрь'
                },
            },

            init() {
                this.normalizeEmptyValue();
                this.updateDateFromValue();
                this.$watch('value', () => {
                    this.normalizeEmptyValue();
                    this.updateDateFromValue();
                });
            },

            parseYearMonth(raw) {
                if (!raw) {
                    return null;
                }

                const parts = String(raw).slice(0, 10).split('-');
                if (parts.length < 2) {
                    return null;
                }

                const year = parseInt(parts[0], 10);
                const month = parseInt(parts[1], 10) - 1;
                if (Number.isNaN(year) || Number.isNaN(month)) {
                    return null;
                }

                return { year, month };
            },

            parseBound(attribute) {
                const bounds = this.$root?.parentElement?.querySelector('.monthpicker-bounds');
                const sources = [bounds, this.$root, this.$el];

                for (const el of sources) {
                    const parsed = this.parseYearMonth(el?.getAttribute?.(attribute));
                    if (parsed) {
                        return parsed;
                    }
                }

                const fallback = attribute === 'data-min' ? this.min : this.max;

                return this.parseYearMonth(fallback);
            },

            get minBound() {
                return this.parseBound('data-min');
            },

            get maxBound() {
                return this.parseBound('data-max');
            },

            isMonthDisabled(monthIndex) {
                const min = this.minBound;
                const max = this.maxBound;
                const monthIndexNumber = Number(monthIndex);

                if (min && (this.year < min.year || (this.year === min.year && monthIndexNumber < min.month))) {
                    return true;
                }

                if (max && (this.year > max.year || (this.year === max.year && monthIndexNumber > max.month))) {
                    return true;
                }

                return false;
            },

            canGoPrevYear() {
                const min = this.minBound;

                return !min || this.year > min.year;
            },

            canGoNextYear() {
                const max = this.maxBound;

                return !max || this.year < max.year;
            },

            clampYearToBounds() {
                const min = this.minBound;
                const max = this.maxBound;

                if (max && this.year > max.year) {
                    this.year = max.year;
                }

                if (min && this.year < min.year) {
                    this.year = min.year;
                }
            },

            toggle() {
                if (!this.isOpen) {
                    this.clampYearToBounds();
                }

                this.isOpen = !this.isOpen;
            },

            close() {
                this.isOpen = false;
            },

            /**
             * Livewire иногда отдаёт null Carbon как эпоху (1970-01-01).
             * Для UI и модели это «пустое» значение.
             */
            isEmptyDate(value) {
                if (value === null || value === undefined || value === '') {
                    return true;
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return true;
                }

                return date.getUTCFullYear() === 1970
                    && date.getUTCMonth() === 0
                    && date.getUTCDate() === 1;
            },

            hasValue() {
                return ! this.isEmptyDate(this.value);
            },

            normalizeEmptyValue() {
                if (this.value !== null && this.isEmptyDate(this.value)) {
                    this.value = null;
                }
            },

            updateDateFromValue() {
                if (! this.hasValue()) {
                    const now = new Date();
                    this.year = now.getFullYear();
                    this.month = now.getMonth();
                    this.clampYearToBounds();

                    return;
                }

                const date = new Date(this.value);
                this.year = date.getFullYear();
                this.month = date.getMonth();
                this.clampYearToBounds();
            },

            nextYear() {
                if (! this.canGoNextYear()) {
                    return;
                }

                this.year++;
            },

            prevYear() {
                if (! this.canGoPrevYear()) {
                    return;
                }

                this.year--;
            },

            selectMonth(monthIndex) {
                if (this.isMonthDisabled(monthIndex)) {
                    return;
                }

                this.month = monthIndex;
                this.updateValue();
                this.isOpen = false;
            },

            updateValue() {
                const date = new Date(Date.UTC(this.year, this.month, 1));
                this.value = date.toISOString();
            },

            get displayValue() {
                if (! this.hasValue()) {
                    return this.placeholder;
                }

                const date = new Date(this.value);
                const year = date.getFullYear();
                const month = this.monthMap[date.getMonth()].full;
                return month + ' ' + year;
            },

            monthSelected(monthIndex) {
                if (! this.hasValue()) {
                    return false;
                }

                const date = new Date(this.value);
                const year = date.getFullYear();
                const monthVal = date.getMonth();
                return (year == this.year) && (monthIndex == monthVal);
            }
        }));
    </script>
    @endscript

    <style>
        .monthpicker-wrap {
            min-width: 0;
            max-width: 100%;
        }

        .monthpicker {
            color: #486388;
            position: relative;
            min-width: 0;
            max-width: 100%;
        }

        .monthpicker-trigger {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid {{ $borderColor }};
            border-radius: 5px;
            padding: 6px 10px;
            background: none;
            color: inherit;
            font: inherit;
            width: 100%;
            min-width: 0;
            max-width: 100%;
            min-height: 42px;
            box-sizing: border-box;
        }

        .monthpicker-trigger__icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            display: flex;
        }

        .monthpicker-trigger__icon svg {
            width: 100%;
            height: 100%;
        }

        .monthpicker-trigger__label {
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .monthpicker-trigger__label--placeholder {
            color: #94A8C1;
        }

        .monthpicker-dropdown {
            background-color: #FFFFFF;
            padding: 5px 5px 10px;
            z-index: 100;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgb(0 0 0 / .08);
        }

        .monthpicker-year-nav {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
        }

        .monthpicker-year-nav__label {
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            font-weight: 700;
        }

        .monthpicker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px;
        }

        .monthpicker-btn {
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            cursor: pointer;
            background: none;
            color: inherit;
            font-family: inherit;
            transition: background-color .15s, color .15s, border-color .15s;

            &:hover,
            &.selected {
                background-color: #599CFF;
                color: #FFFFFF;
                border-color: transparent;
            }

            &:disabled {
                opacity: .35;
                cursor: not-allowed;
            }

            &:disabled:hover {
                background: none;
                color: inherit;
                border-color: #C4D0E0;
            }
        }

        .monthpicker-btn--square {
            width: 32px;
            height: 32px;
        }

        .monthpicker-btn--month {
            min-width: 73px;
            padding: 4px 8px;
        }
    </style>
@endonce
