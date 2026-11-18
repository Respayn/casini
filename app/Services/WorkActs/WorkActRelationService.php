<?php

namespace App\Services\WorkActs;

use App\Helpers\StringHelper;
use App\Models\SaoPerformedWorkAct;
use Illuminate\Support\Collection;

class WorkActRelationService
{
    /**
     * @param  list<string>  $additionalAgreementNumbers
     */
    public function relatesToProject(
        SaoPerformedWorkAct $workAct,
        string $mainContractNumber,
        array $additionalAgreementNumbers,
        ?string $projectInn,
    ): bool {
        $normalizedProjectContractNumber = StringHelper::normalizeContractNumber($mainContractNumber);
        $normalizedAdditionalNumbers = collect($additionalAgreementNumbers)
            ->map(fn (string $number) => StringHelper::normalizeAdditionalNumber($number))
            ->filter(fn (string $number) => $number !== '')
            ->values();

        $additionalNumbersEmpty = $normalizedAdditionalNumbers->isEmpty();
        $projectContractNumbersEmpty = $normalizedProjectContractNumber === '' && $additionalNumbersEmpty;

        if ($projectContractNumbersEmpty) {
            return false;
        }

        $normalizedWorkActContractNumber = StringHelper::normalizeContractNumber(
            (string) ($workAct->contract_number ?? '')
        );
        $normalizedWorkActAdditionalNumber = StringHelper::normalizeAdditionalNumber(
            $workAct->customer_additional_number
        );
        $actContractNumbersEmpty = $normalizedWorkActContractNumber === ''
            && $normalizedWorkActAdditionalNumber === '';

        if (
            $actContractNumbersEmpty
            && filled($projectInn)
            && (string) $projectInn === (string) ($workAct->customer_inn ?? '')
        ) {
            return true;
        }

        if (
            $normalizedProjectContractNumber !== ''
            && $normalizedProjectContractNumber === $normalizedWorkActContractNumber
            && (
                $additionalNumbersEmpty
                || $normalizedAdditionalNumbers->contains($normalizedWorkActAdditionalNumber)
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function parseAdditionalAgreementNumbers(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $number) => trim($number))
            ->filter(fn (string $number) => $number !== '')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object{
     *     projectId: int,
     *     mainContractNumber: string,
     *     additionalAgreementNumbers: list<string>,
     *     inn: ?string
     * }>  $projectContexts
     */
    public function findBestMatchingProjectId(
        SaoPerformedWorkAct $workAct,
        Collection $projectContexts,
    ): ?int {
        $sums = $this->sumActsByProject($projectContexts, collect([$workAct]));

        foreach ($sums as $projectId => $sum) {
            if ($sum !== null) {
                return (int) $projectId;
            }
        }

        return null;
    }

    /**
     * Сопоставляет акты месяца с клиенто-проектами. Один акт - один проект.
     * Сначала совпадение по договору, потом запасной вариант по ИНН.
     *
     * @param  Collection<int, object{
     *     projectId: int,
     *     mainContractNumber: string,
     *     additionalAgreementNumbers: list<string>,
     *     inn: ?string
     * }>  $projectContexts
     * @param  Collection<int, SaoPerformedWorkAct>  $workActs
     * @return array<int, float|null>
     */
    public function sumActsByProject(Collection $projectContexts, Collection $workActs): array
    {
        $sums = [];
        foreach ($projectContexts as $context) {
            $sums[(int) $context->projectId] = null;
        }

        if ($workActs->isEmpty() || $projectContexts->isEmpty()) {
            return $sums;
        }

        $remainingActs = $workActs->values();

        $remainingActs = $this->assignActs(
            $remainingActs,
            $projectContexts,
            $sums,
            preferContractMatch: true,
        );
        $this->assignActs(
            $remainingActs,
            $projectContexts,
            $sums,
            preferContractMatch: false,
        );

        return $sums;
    }

    /**
     * @param  Collection<int, SaoPerformedWorkAct>  $workActs
     * @param  Collection<int, object>  $projectContexts
     * @param  array<int, float|null>  $sums
     * @return Collection<int, SaoPerformedWorkAct>
     */
    private function assignActs(
        Collection $workActs,
        Collection $projectContexts,
        array &$sums,
        bool $preferContractMatch,
    ): Collection {
        return $workActs->reject(function (SaoPerformedWorkAct $workAct) use (
            $projectContexts,
            &$sums,
            $preferContractMatch,
        ) {
            foreach ($projectContexts as $context) {
                $matches = $this->relatesToProject(
                    $workAct,
                    (string) $context->mainContractNumber,
                    $context->additionalAgreementNumbers,
                    $context->inn,
                );

                if (! $matches) {
                    continue;
                }

                $isContractMatch = $this->isContractMatch(
                    $workAct,
                    (string) $context->mainContractNumber,
                    $context->additionalAgreementNumbers,
                );

                if ($preferContractMatch && ! $isContractMatch) {
                    continue;
                }

                if (! $preferContractMatch && $isContractMatch) {
                    continue;
                }

                $projectId = (int) $context->projectId;
                $sums[$projectId] = ($sums[$projectId] ?? 0.0) + (float) $workAct->price;

                return true;
            }

            return false;
        })->values();
    }

    /**
     * @param  list<string>  $additionalAgreementNumbers
     */
    private function isContractMatch(
        SaoPerformedWorkAct $workAct,
        string $mainContractNumber,
        array $additionalAgreementNumbers,
    ): bool {
        $normalizedProjectContractNumber = StringHelper::normalizeContractNumber($mainContractNumber);
        if ($normalizedProjectContractNumber === '') {
            return false;
        }

        $normalizedWorkActContractNumber = StringHelper::normalizeContractNumber(
            (string) ($workAct->contract_number ?? '')
        );
        if ($normalizedProjectContractNumber !== $normalizedWorkActContractNumber) {
            return false;
        }

        $normalizedAdditionalNumbers = collect($additionalAgreementNumbers)
            ->map(fn (string $number) => StringHelper::normalizeAdditionalNumber($number))
            ->filter(fn (string $number) => $number !== '')
            ->values();

        if ($normalizedAdditionalNumbers->isEmpty()) {
            return true;
        }

        $normalizedWorkActAdditionalNumber = StringHelper::normalizeAdditionalNumber(
            $workAct->customer_additional_number
        );

        return $normalizedAdditionalNumbers->contains($normalizedWorkActAdditionalNumber);
    }
}
