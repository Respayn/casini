<?php

namespace Src\Domain\YandexMetrika;

use DateTimeImmutable;

/**
 * Данные о достижении цели из отчёта "Конверсии" Яндекс.Метрики.
 */
class YandexMetrikaGoalConversion
{
    public function __construct(
        private ?int $id,
        private int $projectId,
        private string $goalName,
        private DateTimeImmutable $month,
        private int $conversions
    ) {}

    public static function restore(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            $data['project_id'],
            $data['goal_name'],
            new DateTimeImmutable($data['month']),
            (int) $data['conversions']
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getGoalName(): string
    {
        return $this->goalName;
    }

    public function getMonth(): DateTimeImmutable
    {
        return $this->month;
    }

    public function getConversions(): int
    {
        return $this->conversions;
    }
}
