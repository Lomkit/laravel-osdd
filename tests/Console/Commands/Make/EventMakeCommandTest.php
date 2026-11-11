<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class EventMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Events'));

        parent::tearDown();
    }

    public function test_it_generates_event_file_in_correct_path(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/UserRegistered.php');
    }

    public function test_it_generates_event_with_correct_namespace(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Events;',
            'class UserRegistered',
        ], 'functional/test-layer/src/Events/UserRegistered.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:event', ['name' => 'UserRegistered'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/UserRegistered.php');
    }

    public function test_it_generates_nested_event_in_correct_path(): void
    {
        $this->artisan('osdd:event', ['name' => 'Auth/UserLoggedIn', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Events/Auth/UserLoggedIn.php');
    }

    public function test_it_generates_nested_event_with_correct_namespace(): void
    {
        $this->artisan('osdd:event', ['name' => 'Auth/UserLoggedIn', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Events\Auth;',
            'class UserLoggedIn',
        ], 'functional/test-layer/src/Events/Auth/UserLoggedIn.php');
    }
}
