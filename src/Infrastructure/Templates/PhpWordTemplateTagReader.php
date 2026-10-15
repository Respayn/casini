<?php

namespace Src\Infrastructure\Templates;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use Src\Application\Templates\TemplateTagReaderInterface;
use Throwable;

class PhpWordTemplateTagReader implements TemplateTagReaderInterface
{
    public function read(string $path): ?array
    {
        if (! Storage::exists($path)) {
            return null;
        }

        try {
            $variables = (new TemplateProcessor(Storage::path($path)))->getVariables();
        } catch (Throwable) {
            return null;
        }

        return array_values(array_filter(
            $variables,
            fn (string $variable): bool => ! str_starts_with($variable, '/'),
        ));
    }
}
