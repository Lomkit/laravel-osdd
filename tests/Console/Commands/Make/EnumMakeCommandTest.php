<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class EnumMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Enums'));

        parent::tearDown();
    }

    public function test_it_generates_enum_file_in_correct_path(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Enums/Status.php');
    }

    public function test_it_generates_enum_with_correct_namespace(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Enums;',
            'enum Status',
        ], 'functional/test-layer/src/Enums/Status.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Enums/Status.php');
    }
}
