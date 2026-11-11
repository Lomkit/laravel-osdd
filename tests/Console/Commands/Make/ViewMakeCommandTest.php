<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ViewMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/resources'));

        parent::tearDown();
    }

    public function test_it_generates_view_file_in_correct_path(): void
    {
        $this->artisan('osdd:view', ['name' => 'index', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/resources/views/index.blade.php');
    }

    public function test_it_generates_nested_view_in_correct_path(): void
    {
        $this->artisan('osdd:view', ['name' => 'users/index', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/resources/views/users/index.blade.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:view', ['name' => 'index'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/resources/views/index.blade.php');
    }
}
