<?php

namespace App\Services;

use App\Data\RateValueData;
use App\Livewire\Forms\SystemSettings\Dictionaries\CreateRateForm;
use App\Repositories\RateRepository;
use App\Repositories\RateValueRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RateService
{
    private RateRepository $rateRepository;
    private RateValueRepository $rateValueRepository;

    public function __construct(RateRepository $rateRepository, RateValueRepository $rateValueRepository)
    {
        $this->rateRepository = $rateRepository;
        $this->rateValueRepository = $rateValueRepository;
    }

    public function getRates(): Collection
    {
        $today = now()->setTime(0, 0);

        $rates = $this->rateRepository->all(with: ['values']);
        $rates = $rates->map(function ($rate) use ($today) {
            $rate->values = $rate->values->sortByDesc('startDate')->values();

            $rate->values = $this->setRateValuesEndDates($rate->values);

            foreach ($rate->values as $rateValue) {
                $rate->actualValue = $rateValue->value;
                $rate->actualStartDate = $rateValue->startDate;
                $rate->actualEndDate = $rateValue->endDate;
                if ($rateValue->startDate->lte($today)) {
                    break;
                }
            }
            return $rate;
        });

        return $rates;
    }

    private function setRateValuesEndDates(Collection $rateValues): Collection
    {
        for ($i = 0; $i < $rateValues->count() - 1; $i++) {
            $rateValues[$i + 1]->endDate = $rateValues[$i]->startDate->subDay();
        }

        return $rateValues;
    }

    public function createRate(CreateRateForm $form)
    {
        $rateAttributes = $form->only('name');
        $rateValueAttributes = $form->except('name');

        DB::transaction(function () use ($rateAttributes, $rateValueAttributes) {
            $rateId = $this->rateRepository->save($rateAttributes);
            $rateValueAttributes['rate_id'] = $rateId;
            $this->rateValueRepository->save(Arr::snake($rateValueAttributes));
        });
    }

    public function updateRate(CreateRateForm $form, int $rateId)
    {
        $rateAttributes = $form->only('name');
        $rateAttributes['id'] = $rateId;
        $rateValueAttributes = $form->except('name');

        DB::transaction(function () use ($rateAttributes, $rateValueAttributes) {
            $rateId = $this->rateRepository->save($rateAttributes);
            $rateValues = $this->rateValueRepository->findBy('rate_id', $rateId);
            foreach ($rateValues as $rateValue) {
                if (
                    $rateValue->startDate->year === $rateValueAttributes['startDate']->year
                    && $rateValue->startDate->month === $rateValueAttributes['startDate']->month
                    && $rateValue->startDate->day === $rateValueAttributes['startDate']->day
                ) {
                    $rateValueAttributes['id'] = $rateValue->id;
                    break;
                }
            }
            $rateValueAttributes['rate_id'] = $rateId;
            $this->rateValueRepository->save(Arr::snake($rateValueAttributes));
        });
    }

    public function deleteRate(int $rateId)
    {
        $rateValuesIds = $this->rateValueRepository->findBy('rate_id', $rateId)->pluck('id');

        DB::transaction(function () use ($rateId, $rateValuesIds) {
            $this->rateRepository->delete($rateId);
            foreach ($rateValuesIds as $rateValueId) {
                $this->rateValueRepository->delete($rateValueId);
            }
        });
    }
}
