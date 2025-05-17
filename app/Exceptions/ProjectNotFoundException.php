<?php

namespace App\Exceptions;

use Exception;

class ProjectNotFoundException extends Exception
{
    public function __construct(
        public readonly string $inn,
        public readonly string $contractNumber,
        public readonly ?string $additionalNumbers
    ) {
        parent::__construct(
            "Project not found for:
            INN={$inn},
            Contract={$contractNumber},
            Additional={$additionalNumbers}"
        );
    }
}
