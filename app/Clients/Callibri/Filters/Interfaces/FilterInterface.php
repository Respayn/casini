<?php

namespace App\Clients\Callibri\Filters\Interfaces;

interface FilterInterface
{
    public function apply(array $leads): array;
}
