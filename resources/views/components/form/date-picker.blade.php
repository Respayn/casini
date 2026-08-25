@props([
    'placeholder' => '',
    'required' => false,
    'min' => null,
    'max' => null,
])

<span
    x-data="{
        showDatepicker: false,
        datepickerValue: '',
        month: null,
        year: null,
        min: @js($min),
        max: @js($max),

        init() {
            this.initDate();
        },

        toggleDatepicker() {
            if (this.showDatepicker) {
                this.showDatepicker = false;
            } else {
                this.initDate();
                this.clampMonthToBounds();
                this.showDatepicker = true;
            }
        },

        initDate() {
            let date = null;
            if (this.datepickerValue) {
                date = new Date(this.datepickerValue);
            } else {
                date = new Date();
            }

            this.month = date.getMonth();
            this.year = date.getFullYear();
        },

        parseBound(value) {
            if (!value) {
                return null;
            }

            const parts = String(value).slice(0, 10).split('-');
            if (parts.length < 3) {
                return null;
            }

            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            if (Number.isNaN(year) || Number.isNaN(month) || Number.isNaN(day)) {
                return null;
            }

            return { year, month, day };
        },

        compareDay(year, month, day, bound) {
            if (year !== bound.year) {
                return year - bound.year;
            }
            if (month !== bound.month) {
                return month - bound.month;
            }

            return day - bound.day;
        },

        isDayDisabled(year, month, day) {
            const min = this.parseBound(this.min);
            const max = this.parseBound(this.max);

            if (min && this.compareDay(year, month, day, min) < 0) {
                return true;
            }

            if (max && this.compareDay(year, month, day, max) > 0) {
                return true;
            }

            return false;
        },

        canGoPrevMonth() {
            const min = this.parseBound(this.min);
            if (!min) {
                return true;
            }

            if (this.year > min.year) {
                return true;
            }

            return this.year === min.year && this.month > (min.month - 1);
        },

        canGoNextMonth() {
            const max = this.parseBound(this.max);
            if (!max) {
                return true;
            }

            if (this.year < max.year) {
                return true;
            }

            return this.year === max.year && this.month < (max.month - 1);
        },

        clampMonthToBounds() {
            const min = this.parseBound(this.min);
            const max = this.parseBound(this.max);

            if (max && (this.year > max.year || (this.year === max.year && this.month > (max.month - 1)))) {
                this.year = max.year;
                this.month = max.month - 1;
            }

            if (min && (this.year < min.year || (this.year === min.year && this.month < (min.month - 1)))) {
                this.year = min.year;
                this.month = min.month - 1;
            }
        },

        nextMonth() {
            if (!this.canGoNextMonth()) {
                return;
            }

            this.month++;
            if (this.month > 11) {
                this.month = 0;
                this.year++;
            }
        },

        prevMonth() {
            if (!this.canGoPrevMonth()) {
                return;
            }

            this.month--;
            if (this.month < 0) {
                this.month = 11;
                this.year--;
            }
        },

        selectDate(year, month, day) {
            if (this.isDayDisabled(year, month, day)) {
                return;
            }

            const selectedDate = new Date(year, month - 1, day);
            this.datepickerValue = selectedDate.getFullYear() + '-' + ('0' + (selectedDate.getMonth() + 1)).slice(-2) + '-' + ('0' + selectedDate.getDate()).slice(-2);
            this.showDatepicker = false;
        },

        get monthName() {
            const names = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
            return names[this.month];
        },

        get formattedDate() {
            return this.datepickerValue ? new Date(this.datepickerValue).toLocaleDateString('ru-RU') : '';
        },

        get days() {
            const firstDayOfMonth = new Date(this.year, this.month, 1);
            const calendarStart = new Date(firstDayOfMonth);
            const startDiff = calendarStart.getDay() >= 1 ? calendarStart.getDay() - 1 : 6 - calendarStart.getDay();
            calendarStart.setDate(calendarStart.getDate() - startDiff);

            const lastDayOfMonth = new Date(this.year, this.month + 1, 0);
            const calendarEnd = new Date(lastDayOfMonth);
            const endDiff = 7 - calendarEnd.getDay();
            calendarEnd.setDate(calendarEnd.getDate() + endDiff);

            const days = [];
            const selectedDate = this.datepickerValue ?
                new Date(this.datepickerValue) :
                null;

            for (let date = new Date(calendarStart); date <= calendarEnd; date.setDate(date.getDate() + 1)) {
                const year = date.getFullYear();
                const month = date.getMonth() + 1;
                const day = date.getDate();

                days.push({
                    year,
                    month,
                    day,
                    inMonth: date.getMonth() === this.month,
                    disabled: this.isDayDisabled(year, month, day),
                    selected: this.datepickerValue ?
                        date.getFullYear() === selectedDate.getFullYear() && date.getMonth() === selectedDate.getMonth() && date.getDate() === selectedDate.getDate() : false
                });
            }

            return days;
        }
    }"
    x-modelable="datepickerValue"
    {{ $attributes->class(['inline-flex max-w-full']) }}
    {{ $attributes->wire('model') }}
>
    <input
        type="text"
        @class([
            'border-input-border min-h-[42px] w-full rounded-[5px] border px-3',
        ])
        placeholder="{{ $placeholder }}"
        @required($required)
        readonly
        x-ref="input"
        x-on:click="toggleDatepicker"
        x-on:keydown.escape="showDatepicker = false"
        x-bind:value="formattedDate"
    />
    <div
        class="flex flex-col items-center gap-2 rounded-md bg-white p-1 shadow z-10"
        x-anchor="$refs.input"
        x-show="showDatepicker"
        x-on:click.outside="showDatepicker = false"
        x-cloak
        x-transition
    >
        <div class="flex w-full gap-1">
            <x-button.button
                size="sm"
                icon="icons.accordion-arrow"
                iconClasses="rotate-90"
                x-bind:disabled="!canGoPrevMonth()"
                x-on:click="prevMonth"
            />
            <x-button.button
                class="flex-1"
                type="button"
                size="sm"
            >
                <x-slot:label>
                    <span x-text="monthName + ' ' + year"></span>
                </x-slot>
            </x-button.button>
            <x-button.button
                size="sm"
                icon="icons.accordion-arrow"
                iconClasses="rotate-270"
                x-bind:disabled="!canGoNextMonth()"
                x-on:click="nextMonth"
            />
        </div>
        <div class="grid grid-cols-7">
            <div class="*:bg-table-column contents text-sm font-bold text-white">
                <div class="flex h-8 w-8 items-center justify-center rounded-l">Пн</div>
                <div class="flex h-8 w-8 items-center justify-center">Вт</div>
                <div class="flex h-8 w-8 items-center justify-center">Ср</div>
                <div class="flex h-8 w-8 items-center justify-center">Чт</div>
                <div class="flex h-8 w-8 items-center justify-center">Пт</div>
                <div class="flex h-8 w-8 items-center justify-center">Сб</div>
                <div class="flex h-8 w-8 items-center justify-center rounded-r">Вс</div>
            </div>
            <div class="contents text-center *:rounded-md">
                <template x-for="day in days">
                    <div
                        class="flex h-8 w-8 items-center justify-center"
                        x-text="day.day"
                        x-bind:style="day.disabled ? 'opacity: .35; cursor: not-allowed;' : null"
                        x-bind:class="{
                            'text-secondary-text': !day.inMonth,
                            'hover:bg-primary hover:cursor-pointer hover:text-white': !day.disabled,
                            'bg-primary text-white': day.selected && !day.disabled
                        }"
                        x-on:click="selectDate(day.year, day.month, day.day)"
                    ></div>
                </template>
            </div>
        </div>
    </div>
</span>
