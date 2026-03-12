<?php

namespace Src\Domain\Templates;

use Exception;

class TemplateInUseException extends Exception
{
    protected $message = 'Нельзя удалить шаблон: он используется в отчетах.';
}