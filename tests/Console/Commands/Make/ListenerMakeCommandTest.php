<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ListenerMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Listeners'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Events'));

        parent::tearDown();
    }

    public function test_it_generates_listener_file_in_correct_path(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function test_it_generates_listener_with_correct_namespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Listeners;',
            'class SendWelcomeEmail',
        ], 'functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function test_it_generates_listener_with_event_in_layer_namespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'SendWelcomeEmail', '--event' => 'UserRegistered', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use Functional\TestLayer\Events\UserRegistered;',
        ], 'functional/test-layer/src/Listeners/SendWelcomeEmail.php');
    }

    public function test_it_generates_nested_listener_in_correct_path(): void
    {
        $this->artisan('osdd:listener', ['name' => 'Auth/HandleLogin', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Listeners/Auth/HandleLogin.php');
    }

    public function test_it_generates_nested_listener_with_correct_namespace(): void
    {
        $this->artisan('osdd:listener', ['name' => 'Auth/HandleLogin', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Listeners\Auth;',
            'class HandleLogin',
        ], 'functional/test-layer/src/Listeners/Auth/HandleLogin.php');
    }
}
