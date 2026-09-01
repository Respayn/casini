<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Data\BonusConditionData;
use App\Models\ProjectBonusCondition;
use Livewire\Attributes\Rule;
use Livewire\Form;

class ProjectBonusGuaranteeForm extends Form
{
    #[Rule('boolean')]
    public bool $bonusesEnabled = false;

    #[Rule('boolean')]
    public bool $calculateInPercentage = false;

    #[Rule('nullable|numeric|min:0')]
    public ?float $clientPayment = null;

    #[Rule('required_if:bonusesEnabled,true|integer|in:1,2,3')]
    public int $startMonth = 1;

    public array $intervals = [
        [
            'fromPercentage' => '',
            'toPercentage' => '',
            'bonusAmount' => '',
            'bonusPercentage' => '',
        ],
    ];

    public function rules()
    {
        $rules = [
            'bonusesEnabled' => 'boolean',
            'calculateInPercentage' => 'boolean',
            'clientPayment' => 'nullable|numeric|min:0',
            'startMonth' => 'required_if:bonusesEnabled,true|integer|in:1,2,3',
            'intervals' => 'required_if:bonusesEnabled,true|array|min:1',
            'intervals.*.fromPercentage' => 'required_if:bonusesEnabled,true|numeric|min:0|max:9999.99',
            'intervals.*.toPercentage' => 'required_if:bonusesEnabled,true|numeric|min:0|max:9999.99|gte:intervals.*.fromPercentage',
        ];

        if (! $this->calculateInPercentage) {
            $rules['intervals.*.bonusAmount'] = 'required_if:bonusesEnabled,true|numeric';
        } else {
            $rules['intervals.*.bonusPercentage'] = 'required_if:bonusesEnabled,true|numeric';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'intervals.*.fromPercentage.required_if' => 'Укажите начало диапазона выполнения плана.',
            'intervals.*.toPercentage.required_if' => 'Укажите конец диапазона выполнения плана.',
            'intervals.*.toPercentage.gte' => 'Значение «До» не должно быть меньше значения «От».',
            'intervals.*.fromPercentage.max' => 'Значение «От» не может быть больше 9999,99%.',
            'intervals.*.toPercentage.max' => 'Значение «До» не может быть больше 9999,99%.',
            'intervals.*.bonusAmount.required_if' => 'Укажите сумму бонуса или гарантии.',
            'intervals.*.bonusPercentage.required_if' => 'Укажите процент бонуса или гарантии.',
            'startMonth.required_if' => 'Выберите месяц начала расчёта бонусов и гарантий.',
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'intervals.*.fromPercentage' => 'начало диапазона',
            'intervals.*.toPercentage' => 'конец диапазона',
            'intervals.*.bonusAmount' => 'сумма бонуса или гарантии',
            'intervals.*.bonusPercentage' => 'процент бонуса или гарантии',
            'startMonth' => 'месяц начала расчёта',
            'clientPayment' => 'чек клиента',
        ];
    }

    public function validate($rules = null, $messages = [], $attributes = [], $dataOverrides = [])
    {
        $this->normalizeIntervalNumericFields();

        return parent::validate($rules, $messages, $attributes, $dataOverrides);
    }

    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = [])
    {
        $this->normalizeIntervalNumericFields();

        return parent::validateOnly($field, $rules, $messages, $attributes, $dataOverrides);
    }

    private function normalizeIntervalNumericFields(): void
    {
        foreach ($this->intervals as $index => $interval) {
            if (! is_array($interval)) {
                continue;
            }

            foreach (['fromPercentage', 'toPercentage', 'bonusAmount', 'bonusPercentage'] as $key) {
                if (! array_key_exists($key, $interval) || ! is_string($interval[$key])) {
                    continue;
                }

                $clean = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $interval[$key]);
                $this->intervals[$index][$key] = $clean ?? $interval[$key];
            }
        }

        if (is_string($this->clientPayment)) {
            $cleanPayment = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', (string) $this->clientPayment);
            $this->clientPayment = $cleanPayment === '' ? null : (float) $cleanPayment;
        }
    }

    /**
     * Метод для заполнения данных формы из модели бонусных условий.
     *
     * @return void
     */
    public function from(BonusConditionData|ProjectBonusCondition $bonusCondition)
    {
        $this->bonusesEnabled = $bonusCondition->bonuses_enabled;
        $this->calculateInPercentage = $bonusCondition->calculate_in_percentage;
        $this->clientPayment = $bonusCondition->client_payment;
        $this->startMonth = $bonusCondition->start_month;
        $this->intervals = $bonusCondition->intervals->toArray();
    }
}
