<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class FactoryMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/factories'));

        parent::tearDown();
    }

    public function test_it_generates_factory_file_in_correct_path(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/UserFactory.php');
    }

    public function test_it_generates_factory_with_correct_namespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Factories;',
            'class UserFactory extends Factory',
        ], 'functional/test-layer/database/factories/UserFactory.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/UserFactory.php');
    }

    public function test_it_generates_nested_factory_in_correct_path(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/Admin/UserFactory.php');
    }

    public function test_it_generates_nested_factory_with_correct_namespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Factories\Admin;',
            'class UserFactory extends Factory',
        ], 'functional/test-layer/database/factories/Admin/UserFactory.php');
    }
}
