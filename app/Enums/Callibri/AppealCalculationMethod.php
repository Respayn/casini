<?php

namespace App\Enums\Callibri;

enum AppealCalculationMethod: string
{
    case ALL = 'all';
    case UNIQUE = 'unique';
    case FIRST = 'first';
    case CLASS_BASED = 'class_based';
}
