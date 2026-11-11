<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ResourceMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http/Resources'));

        parent::tearDown();
    }

    public function test_it_generates_resource_file_in_correct_path(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Resources/UserResource.php');
    }

    public function test_it_generates_resource_with_correct_namespace(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Resources;',
            'class UserResource extends JsonResource',
        ], 'functional/test-layer/src/Http/Resources/UserResource.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:resource', ['name' => 'UserResource'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Resources/UserResource.php');
    }
}
