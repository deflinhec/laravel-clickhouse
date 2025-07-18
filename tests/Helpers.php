<?php

declare(strict_types=1);

namespace Deflinhec\LaravelClickHouse\Tests;

use Faker\Factory;
use Faker\Generator;
use Mockery;

trait Helpers
{
    protected function mock(string $abstract): Mockery\MockInterface
    {
        return Mockery::mock($abstract);
    }

    protected function faker(): Generator
    {
        return Factory::create();
    }
}
