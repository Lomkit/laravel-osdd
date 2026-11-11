<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class TestMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/tests'));

        parent::tearDown();
    }

    public function test_it_generates_unit_test_file_in_correct_path(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/UserTest.php');
    }

    public function test_it_generates_unit_test_with_correct_namespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Unit;',
            'class UserTest',
        ], 'functional/test-layer/tests/Unit/UserTest.php');
    }

    public function test_it_generates_feature_test_file_in_correct_path(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Feature/UserTest.php');
    }

    public function test_it_generates_feature_test_with_correct_namespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Feature;',
            'class UserTest',
        ], 'functional/test-layer/tests/Feature/UserTest.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:test', ['name' => 'UserTest', '--unit' => true])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/UserTest.php');
    }

    public function test_it_generates_nested_unit_test_in_correct_path(): void
    {
        $this->artisan('osdd:test', ['name' => 'Services/UserServiceTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/tests/Unit/Services/UserServiceTest.php');
    }

    public function test_it_generates_nested_unit_test_with_correct_namespace(): void
    {
        $this->artisan('osdd:test', ['name' => 'Services/UserServiceTest', '--layer' => 'functional/test-layer', '--unit' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Tests\Unit\Services;',
            'class UserServiceTest',
        ], 'functional/test-layer/tests/Unit/Services/UserServiceTest.php');
    }
}
