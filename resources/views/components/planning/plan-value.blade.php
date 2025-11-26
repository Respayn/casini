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
                return parameter.plans?.[this.month] ?? null;
            }

            try {
                const formula = parameter.formula;
                const args = parameter.dependencies || [];
                const argv = args.map(argKey => this.findParamValue(argKey));
                
                const func = new Function(...args, 'return ' + formula);
                const result = func(...argv);
                
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
                        maximumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).format(num);
                case 'percent':
                    return new Intl.NumberFormat('ru-RU', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).format(num) + '%';
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
                            parameters[index].plans[month] = this.localValue;

                            $wire.save(index, this.localValue);
                        },

                        cancel() {
                            this.isEditing = false;
                        },
                    }" x-on:click="startEdit()" class="flex items-center justify-end grow px-2.5"
                x-bind:class="{'cursor-pointer hover:bg-gray-50': canEdit && !parameter.is_calculated}">
                <template x-if="isEditing">
                    <div>
                        <x-form.input-text x-ref="input" x-model="localValue" x-on:keydown.enter="commit()"
                            x-on:blur="commit()" x-on:keydown.escape="cancel()" type="number"
                            class="w-full h-full px-1 py-0 bg-white border-none focus:ring-0" />
                    </div>
                </template>

                <template x-if="!isEditing">
                    <div>
                        <span x-text="formatValue(calculateValue(parameter), parameter.format)"></span>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-data.table-cell>