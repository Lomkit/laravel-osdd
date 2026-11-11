<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ExceptionMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Exceptions'));

        parent::tearDown();
    }

    public function test_it_generates_exception_file_in_correct_path(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function test_it_generates_exception_with_correct_namespace(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Exceptions;',
            'class UserNotFoundException extends Exception',
        ], 'functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:exception', ['name' => 'UserNotFoundException'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/UserNotFoundException.php');
    }

    public function test_it_generates_nested_exception_in_correct_path(): void
    {
        $this->artisan('osdd:exception', ['name' => 'Auth/InvalidTokenException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Exceptions/Auth/InvalidTokenException.php');
    }

    public function test_it_generates_nested_exception_with_correct_namespace(): void
    {
        $this->artisan('osdd:exception', ['name' => 'Auth/InvalidTokenException', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Exceptions\Auth;',
            'class InvalidTokenException extends Exception',
        ], 'functional/test-layer/src/Exceptions/Auth/InvalidTokenException.php');
    }
}
