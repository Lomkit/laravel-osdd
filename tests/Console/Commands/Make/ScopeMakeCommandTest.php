<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ScopeMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Models/Scopes'));

        parent::tearDown();
    }

    public function test_it_generates_scope_file_in_correct_path(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }

    public function test_it_generates_scope_with_correct_namespace(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Models\Scopes;',
            'class ActiveScope implements Scope',
        ], 'functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:scope', ['name' => 'ActiveScope'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/Scopes/ActiveScope.php');
    }
}
