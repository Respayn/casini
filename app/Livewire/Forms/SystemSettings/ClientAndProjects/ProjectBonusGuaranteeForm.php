<?php

namespace App\Livewire\Forms\SystemSettings\ClientAndProjects;

use App\Data\BonusConditionData;
use App\Models\ProjectBonusCondition;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
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
            'intervals.*.fromPercentage' => 'required_if:bonusesEnabled,true|numeric',
            'intervals.*.toPercentage' => 'required_if:bonusesEnabled,true|numeric|gte:intervals.*.fromPercentage',
        ];

        if (!$this->calculateInPercentage) {
            $rules['intervals.*.bonusAmount'] = 'required_if:bonusesEnabled,true|numeric';
        } else {
            $rules['intervals.*.bonusPercentage'] = 'required_if:bonusesEnabled,true|numeric';
        }

        return $rules;
    }

    /**
     * Метод для заполнения данных формы из модели бонусных условий.
     *
     * @param BonusConditionData|ProjectBonusCondition $bonusCondition
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
