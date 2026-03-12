<?php

namespace Src\Application\ColumnSettings\Get;

use Src\Application\ColumnSettings\ColumnSetting;
use Src\Application\ColumnSettings\ColumnSettingsRepositoryInterface;

class GetColumnSettingsQueryHandler
{
    public function __construct(
        private readonly ColumnSettingsRepositoryInterface $repository
    ) {}

    /**
     * @return ColumnSetting[]|null
     */
    public function handle(GetColumnSettingsQuery $query): ?array
    {
        return $this->repository->find($query->tableId, $query->userId);
    }
}
