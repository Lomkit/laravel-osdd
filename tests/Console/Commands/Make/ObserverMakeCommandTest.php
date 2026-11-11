<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ObserverMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Observers'));

        parent::tearDown();
    }

    public function test_it_generates_observer_file_in_correct_path(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Observers/UserObserver.php');
    }

    public function test_it_generates_observer_with_correct_namespace(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Observers;',
            'class UserObserver',
        ], 'functional/test-layer/src/Observers/UserObserver.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Observers/UserObserver.php');
    }
}
