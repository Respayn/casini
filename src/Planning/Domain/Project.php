<?php

namespace Src\Planning\Domain;

use Src\Planning\Domain\ValueObjects\KpiParametersSchema;
use DateTimeImmutable;
use Src\Planning\Application\Services\KpiParametersSchemaService;
use Src\Planning\Domain\ValueObjects\PlanValue;
use Src\Planning\Domain\ValueObjects\QuarterApproval;
use Src\Shared\ValueObjects\Kpi;
use Src\Shared\ValueObjects\ProjectType;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Project
{
    private int $id;
    private string $name;
    private DateTimeImmutable $createdAt;
    private ProjectType $type;
    private Kpi $kpi;
    private ?Client $client;
    private KpiParametersSchema $parametersSchema;

    /** @var PlanValue[] */
    private array $planValues;

    public function __construct(
        int $id,
        string $name,
        DateTimeImmutable $createdAt,
        ProjectType $type,
        Kpi $kpi,
        ?Client $client = null,
        array $planValues = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->type = $type;
        $this->kpi = $kpi;
        $this->client = $client;
        $this->planValues = $planValues;

        $schemaService = new KpiParametersSchemaService();
        $this->parametersSchema = $schemaService->createSchema($type, $kpi);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getType(): ProjectType
    {
        return $this->type;
    }

    public function getKpi(): Kpi
    {
        return $this->kpi;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getParametersSchema(): KpiParametersSchema
    {
        return $this->parametersSchema;
    }

    /** @return PlanValue[] */
    public function getPlanValues(): array
    {
        return $this->planValues;
    }

    public function getRawPlanValue(string $parameterCode, int $year, int $month): ?float
    {
        $result = null;
        foreach ($this->planValues as $planValue) {
            if ($planValue->getParameterCode() === $parameterCode && $planValue->getYear() === $year && $planValue->getMonth() === $month) {
                $result = $planValue->getValue();
                break;
            }
        }
        return $result;
    }

    public function setPlanValue(string $parameterCode, int $year, int $month, ?float $value): void
    {
        foreach ($this->planValues as $planValue) {
            if ($planValue->getParameterCode() === $parameterCode && $planValue->getYear() === $year && $planValue->getMonth() === $month) {
                $planValue->setValue($value);
                return;
            }
        }

        $this->planValues[] = new PlanValue($parameterCode, $year, $month, $value);
    }

    /**
     * Получить целевое значение плана. Некоторые планы расчитываются по
     * нескольким параметрам.
     * @return ?float
     */
    public function getPlanValue(string $parameterCode, int $year, int $month): ?float
    {
        $plan = null;
        $result = null;

        foreach ($this->getParametersSchema()->getParameters() as $parameter) {
            if ($parameter->getId() === $parameterCode) {
                $plan = $parameter;
            }
        }

        if ($plan === null) {
            return null;
        }

        if ($plan->isCalculated()) {
            $formula = $plan->getFormula();
            $dependencies = $plan->getDependencies();

            $dependenciesValues = [];

            foreach ($dependencies as $dependency) {
                $dependenciesValues[$dependency] = $this->getRawPlanValue($dependency, $year, $month);
            }

            $expressionLanguage = new ExpressionLanguage();

            $result = $expressionLanguage->evaluate($formula, $dependenciesValues);
        } else {
            $result = $this->getRawPlanValue($plan->getId(), $year, $month);
        }

        return $result;
    }

    /**
     * Получить целевое значение плана. Некоторые планы расчитываются по
     * нескольким параметрам.
     * @return ?float
     */
    public function getPrimaryPlanValue(int $year, int $month): ?float
    {
        $primaryPlan = null;
        $result = null;

        foreach ($this->getParametersSchema()->getParameters() as $parameter) {
            if ($parameter->isPrimary()) {
                $primaryPlan = $parameter;
            }
        }

        if ($primaryPlan === null) {
            return null;
        }

        if ($primaryPlan->isCalculated()) {
            $formula = $primaryPlan->getFormula();
            $dependencies = $primaryPlan->getDependencies();

            $dependenciesValues = [];

            foreach ($dependencies as $dependency) {
                $dependenciesValues[$dependency] = $this->getRawPlanValue($dependency, $year, $month);
            }

            $expressionLanguage = new ExpressionLanguage();

            $result = $expressionLanguage->evaluate($formula, $dependenciesValues);
        } else {
            $result = $this->getRawPlanValue($primaryPlan->getId(), $year, $month);
        }

        return $result;
    }
}
