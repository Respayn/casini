<?php

namespace App;

class OperationResult
{
    private bool $success;
    private mixed $value;
    private mixed $error;

    public function __construct(bool $success, mixed $value = null, mixed $error = null)
    {
        $this->success = $success;
        $this->value = $value;
        $this->error = $error;
    }

    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }

    public static function failure(mixed $error = null): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getError(): mixed
    {
        return $this->error;
    }
}
