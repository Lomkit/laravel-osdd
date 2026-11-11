<?php

namespace Lomkit\Osdd\Tests;

use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\OsddServiceProvider;

class OsddServiceProviderTest extends TestCase
{
    public function test_it_publishes_nothing_into_the_root_config_directory(): void
    {
        $this->assertSame([], ServiceProvider::pathsToPublish(OsddServiceProvider::class));
    }
}
