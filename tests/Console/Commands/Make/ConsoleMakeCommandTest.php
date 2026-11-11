<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ConsoleMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Console'));

        parent::tearDown();
    }

    public function test_it_generates_command_file_in_correct_path(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }

    public function test_it_generates_command_with_correct_namespace(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Console\Commands;',
            'class SendReportCommand extends Command',
        ], 'functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:command', ['name' => 'SendReportCommand'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Console/Commands/SendReportCommand.php');
    }
}
