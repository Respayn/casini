<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public $parameters;

    public $month;
    public $rowIndex;
    public $canEdit = false;

    #[On('row-{rowIndex}-updated')]
    public function onRowUpdated(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function save($index, $value)
    {
        if (!$this->canEdit) {
            return;
        }

        if (!empty($this->parameters[$index]['is_calculated'])) {
            return;
        }

        $castedValue = ($value === '' || $value === null) ? null : (float) $value;

        if ($castedValue !== null && in_array($this->parameters[$index]['format'] ?? null, ['integer', 'percent'], true)) {
            $castedValue = round($castedValue);
        }

        $updatedParameters = $this->parameters;
        $updatedParameters[$index]['plans'][$this->month] = $castedValue;

        $this->dispatch(
            'project-plan-updated',
            rowIndex: $this->rowIndex,
            parameters: $updatedParameters,
            month: $this->month
        );
    }
};
?>

<x-data.table-cell class="!p-0 h-1">
    <div class="grid auto-rows-fr h-full divide-y divide-table-cell" x-data="{
        parameters: @js($parameters),
        month: {{ $month }},
        canEdit: @js($canEdit),

        findParamValue(key) {
            const found = this.parameters.find(p => p.key === key);
            return found ? parseFloat(found.plans?.[this.month] || 0) : 0;
        },

        calculateValue(parameter) {
            if (!parameter.is_calculated) {
                const raw = parameter.plans?.[this.month] ?? null;
                if (raw === null || raw === '') return null;
                const num = parseFloat(raw);
                if (isNaN(num)) return null;
                if (parameter.format === 'integer' || parameter.format === 'percent') {
                    return Math.round(num);
                }
                return num;
            }

            try {
                const formula = parameter.formula;
                const args = parameter.dependencies || [];
                const argv = args.map(argKey => this.findParamValue(argKey));
                
                const func = new Function(...args, 'return ' + formula);
                let result = func(...argv);

                if (result === null || result === undefined || Number.isNaN(result)) {
                    parameter.plans[this.month] = null;
                    return null;
                }

                if (parameter.format === 'integer' || parameter.format === 'percent') {
                    result = Math.round(result);
                }
                
                parameter.plans[this.month] = result; 
                
                return result;
            } catch (e) {
                console.error('Formula error', e);
                return 'Err';
            }
        },

        formatValue(value, format) {
            if (value === null || value === '' || isNaN(value)) return '-';
            const num = parseFloat(value);

            switch (format) {
                case 'currency':
                    return new Intl.NumberFormat('ru-RU', { 
                        style: 'currency',
                        currency: 'RUB',
                        maximumFractionDigits: 2
                    }).format(num);
                case 'percent':
                    return new Intl.NumberFormat('ru-RU', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(Math.round(num)) + '%';
                case 'integer':
                    return new Intl.NumberFormat('ru-RU', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(Math.round(num));
                default:
                    return new Intl.NumberFormat('ru-RU', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).format(num);
            }
        }
    }">

        <template x-for="(parameter, index) in parameters" :key="index">
            <div x-data="{
                        isEditing: false,
                        localValue: null,

                        startEdit() {
                            if (!canEdit || parameter.is_calculated || this.isEditing) return;
                            this.isEditing = true;
                            this.localValue = parameters[index].plans[month];
                            this.$nextTick(() => $refs.input.focus());
                        },

                        commit() {
                            this.isEditing = false;
                            let value = this.localValue;
                            if (value !== null && value !== '' && (parameter.format === 'integer' || parameter.format === 'percent')) {
                                const num = parseFloat(value);
                                value = Number.isNaN(num) ? value : Math.round(num);
                            }
                            parameters[index].plans[month] = value;
                            this.localValue = value;

                            $wire.save(index, value);
                        },

                        cancel() {
                            this.isEditing = false;
                        },
                    }"
                x-on:click="startEdit()"
                class="relative flex grow items-center justify-end px-2.5"
                style="min-height: 2.25rem"
                x-bind:class="{'cursor-pointer hover:bg-gray-50': canEdit && !parameter.is_calculated}"
            >
                <span
                    x-show="!isEditing"
                    x-text="formatValue(calculateValue(parameter), parameter.format)"
                ></span>
                <input
                    x-show="isEditing"
                    x-ref="input"
                    type="text"
                    inputmode="decimal"
                    x-model="localValue"
                    x-on:click.stop
                    x-on:keydown.enter="commit()"
                    x-on:blur="commit()"
                    x-on:keydown.escape="cancel()"
                    class="absolute inset-0 w-full border-0 bg-white px-2.5 text-right outline-none"
                    style="min-height: 0; height: 100%; box-sizing: border-box;"
                />
            </div>
        </template>
    </div>
</x-data.table-cell>
