<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;

class JobMiddlewareMakeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Jobs'));

        parent::tearDown();
    }

    public function test_job_middleware_lands_in_the_layer(): void
    {
        $this->artisan('osdd:job-middleware', ['name' => 'RateLimitedOrders', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $path = $this->app->basePath('functional/test-layer/src/Jobs/Middleware/RateLimitedOrders.php');
        require_once $path;

        $this->assertTrue(method_exists('Functional\TestLayer\Jobs\Middleware\RateLimitedOrders', 'handle'));
        $this->assertFileDoesNotExist($this->app->basePath('app/Jobs/Middleware/RateLimitedOrders.php'));
    }
}
