<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Models\ProjectBonusCondition;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectBonusGuaranteeForm extends Form
{
    #[Validate('boolean')]
    public bool $bonusesEnabled = false;

    #[Validate('boolean')]
    public bool $calculateInPercentage = false;

    #[Validate('nullable|numeric|min:0')]
    public ?float $clientPayment = null;

    #[Validate('required_if:bonusesEnabled,true|integer|in:1,2,3')]
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
            'intervals.*.fromPercentage' => 'required|numeric',
            'intervals.*.toPercentage' => 'required|numeric|gte:intervals.*.fromPercentage',
        ];

        if (!$this->calculateInPercentage) {
            $rules['intervals.*.bonusAmount'] = 'required|numeric';
        } else {
            $rules['intervals.*.bonusPercentage'] = 'required|numeric';
        }

        return $rules;
    }

    /**
     * Метод для заполнения данных формы из модели бонусных условий.
     *
     * @param ProjectBonusCondition $bonusCondition
     * @return void
     */
    public function fillFromModel(ProjectBonusCondition $bonusCondition)
    {
        $this->bonusesEnabled = $bonusCondition->bonuses_enabled;
        $this->calculateInPercentage = $bonusCondition->calculate_in_percentage;
        $this->clientPayment = $bonusCondition->client_payment;
        $this->startMonth = $bonusCondition->start_month;
        $this->intervals = $bonusCondition->intervals()->get()->toArray() ?: [
            [
                'fromPercentage' => '',
                'toPercentage' => '',
                'bonusAmount' => '',
                'bonusPercentage' => '',
            ],
        ];
    }

    public function prefixedRules($prefix)
    {
        $rules = $this->rules();

        $prefixedRules = [];
        foreach ($rules as $key => $rule) {
            $prefixedRules[$prefix . $key] = $rule;
        }

        return $prefixedRules;
    }
}
