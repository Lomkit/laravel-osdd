<?php

namespace Lomkit\Osdd\Tests\Concerns;

use Illuminate\Foundation\Application;

trait CompilesViews
{
    protected function defineEnvironment($app): void
    {
        /** @var Application $app */
        $compiledPath = sys_get_temp_dir().'/osdd-compiled-views';

        $app['files']->ensureDirectoryExists($compiledPath);
        $app['config']->set('view.compiled', $compiledPath);
    }
}
