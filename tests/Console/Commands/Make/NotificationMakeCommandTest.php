<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class NotificationMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Notifications'));

        parent::tearDown();
    }

    public function test_it_generates_notification_file_in_correct_path(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function test_it_generates_notification_with_correct_namespace(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Notifications;',
            'class WelcomeNotification extends Notification',
        ], 'functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:notification', ['name' => 'WelcomeNotification'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/WelcomeNotification.php');
    }

    public function test_it_generates_nested_notification_in_correct_path(): void
    {
        $this->artisan('osdd:notification', ['name' => 'Auth/PasswordResetNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Notifications/Auth/PasswordResetNotification.php');
    }

    public function test_it_generates_nested_notification_with_correct_namespace(): void
    {
        $this->artisan('osdd:notification', ['name' => 'Auth/PasswordResetNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Notifications\Auth;',
            'class PasswordResetNotification extends Notification',
        ], 'functional/test-layer/src/Notifications/Auth/PasswordResetNotification.php');
    }
}
