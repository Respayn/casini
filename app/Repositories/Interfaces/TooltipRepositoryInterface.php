<?php

namespace App\Repositories\Interfaces;

interface TooltipRepositoryInterface extends RepositoryInterface
{
    public function findByCode(string $code);
}
