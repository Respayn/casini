<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\PreventsDestructiveDatabaseRefresh;

abstract class TestCase extends BaseTestCase
{
    use PreventsDestructiveDatabaseRefresh;
}
