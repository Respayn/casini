<?php

namespace Src\Application\ColumnSettings\Save;

use Src\Application\ColumnSettings\ColumnSettingsRepositoryInterface;

class SaveColumnSettingsCommandHandler
{
    public function __construct(
        private readonly ColumnSettingsRepositoryInterface $repository
    ) {}

    public function handle(SaveColumnSettingsCommand $command): void
    {
        $this->repository->save(
            $command->tableId,
            $command->userId,
            $command->settings
        );
    }
}
