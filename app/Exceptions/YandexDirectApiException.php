<?php

namespace App\Exceptions;

class YandexDirectApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        \Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
