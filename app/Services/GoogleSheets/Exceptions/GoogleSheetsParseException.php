<?php

namespace App\Services\GoogleSheets\Exceptions;

use RuntimeException;

class GoogleSheetsParseException extends RuntimeException
{
    public function __construct(
        public readonly string $sheetTitle,
        string $message,
    ) {
        parent::__construct($message);
    }
}
