{{-- TODO: объединить этот компонент с компонентом date-picker. Сделать по аналогии с компонентом из библиотеки PrimeVue --}}
<div
    class="monthpicker"
    x-data="monthpicker"
    x-modelable="value"
    {{ $attributes }}
>
    <div
        class="monthpicker-display-container"
        x-ref="displayContainer"
        x-on:click="isOpen = !isOpen"
    >
        <div class="monthpicker-display-icon-container">
            <x-icons.calendar class="monthpicker-display-icon" />
        </div>
        <span
            class="monthpicker-display-label"
            x-text="displayValue"
        ></span>
    </div>

    <div
        class="monthpicker-selector-container"
        x-show="isOpen"
        x-transition
        x-cloak
        x-anchor="$refs.displayContainer"
        x-on:click.outside="isOpen = false"
    >
        <div class="monthpicker-year-selector">
            <button
                class="monthpicker-selector-button h-8 w-8"
                x-on:click="prevYear"
            >
                <x-icons.accordion-arrow class="rotate-90" />
            </button>
            <div
                class="monthpicker-year"
                x-text="year"
            >
            </div>
            <button
                class="monthpicker-selector-button h-8 w-8"
                x-on:click="nextYear"
            >
                <x-icons.accordion-arrow class="rotate-270" />
            </button>
        </div>
        <div class="monthpicker-month-selector">
            <template x-for="(monthData, index) in monthMap">
                <button
                    class="monthpicker-selector-button w-[73px]"
                    x-bind:class="{ 'selected': monthSelected(index) }"
                    x-text="monthData.short"
                    x-on:click="selectMonth(index)"
                ></button>
            </template>
        </div>
    </div>
</div>

@once
    @script
        <script>
            Alpine.data('monthpicker', () => ({
                value: new Date().toISOString(),
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
                        short: 'Май.',
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
                    this.updateDateFromValue();
                },

                updateDateFromValue() {
                    const date = new Date(this.value);
                    this.year = date.getFullYear();
                    this.month = date.getMonth();
                },

                nextYear() {
                    this.year++;
                },

                prevYear() {
                    this.year--;
                },

                selectMonth(monthIndex) {
                    this.month = monthIndex;
                    this.updateValue();
                    this.isOpen = false;
                },

                updateValue() {
                    const date = new Date(Date.UTC(this.year, this.month, 1));
                    this.value = date.toISOString();
                },

                get displayValue() {
                    const date = new Date(this.value);
                    const year = date.getFullYear();
                    const month = this.monthMap[date.getMonth()].full;
                    return month + ' ' + year;
                },

                monthSelected(monthIndex) {
                    const date = new Date(this.value);
                    const year = date.getFullYear();
                    const monthVal = date.getMonth();
                    return (year == this.year) && (monthIndex == monthVal);
                }
            }));
        </script>
    @endscript

    <style>
        .monthpicker {
            color: #486388;
        }

        .monthpicker-display-container {
            cursor: pointer;
            display: flex;
            gap: 10px;
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            padding: 6px 10px;
        }

        .monthpicker-display-label {
            font-size: 14px;
        }

        .monthpicker-display-icon-container {
            width: 20px;
            height: 20px;
        }

        .monthpicker-display-icon {
            width: 100%;
            height: 100%;
        }

        .monthpicker-selector-container {
            background-color: #FFFFFF;
            padding: 5px 5px 10px 5px;
            z-index: 100;
        }

        .monthpicker-selector-button {
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

        .monthpicker-year-selector {
            display: flex;
            gap: 5px;
            margin-bottom: 12px;
        }

        .monthpicker-year {
            border: 1px solid #C4D0E0;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            font-weight: 700;
        }

        .monthpicker-month-selector {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 3px;
        }
    </style>
@endonce
