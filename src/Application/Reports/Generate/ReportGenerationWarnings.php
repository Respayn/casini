<?php

namespace Src\Application\Reports\Generate;

class ReportGenerationWarnings
{
    /** @var list<string> */
    private array $messages = [];

    public function add(string $message): void
    {
        $message = trim($message);

        if ($message === '' || in_array($message, $this->messages, true)) {
            return;
        }

        $this->messages[] = $message;
    }

    /**
     * @param  list<string>  $messages
     */
    public function merge(array $messages): void
    {
        foreach ($messages as $message) {
            $this->add($message);
        }
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->messages;
    }
}
