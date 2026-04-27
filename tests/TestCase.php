<?php

namespace Pexi\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Pexi\Laravel\PexiServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PexiServiceProvider::class,
        ];
    }
}
