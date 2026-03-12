<?php

namespace Src\Shared;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class TypedArray implements ArrayAccess, IteratorAggregate, Countable
{
    private array $items = [];
    private string $type;

    public function __construct(string $className, array $items = [])
    {
        $this->type = $className;

        foreach ($items as $key => $value) {
            $this->validate($value);
            $this->items[$key] = $value;
        }
    }

    public function validate(mixed $item): void
    {
        if (!$item instanceof $this->type) {
            $actual = is_object($item) ? get_class($item) : gettype($item);
            throw new InvalidArgumentException("Expected instance of {$this->type}, got {$actual}");
        }
    }

    // ArrayAccess
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->validate($value);
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // IteratorAggregate
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // Countable
    public function count(): int
    {
        return count($this->items);
    }
}