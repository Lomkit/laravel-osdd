<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class MiddlewareMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http/Middleware'));

        parent::tearDown();
    }

    public function test_it_generates_middleware_file_in_correct_path(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Middleware/Authenticate.php');
    }

    public function test_it_generates_middleware_with_correct_namespace(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Middleware;',
            'class Authenticate',
        ], 'functional/test-layer/src/Http/Middleware/Authenticate.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:middleware', ['name' => 'Authenticate'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Middleware/Authenticate.php');
    }
}
