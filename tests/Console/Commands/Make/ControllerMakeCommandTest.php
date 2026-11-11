<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ControllerMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http'));

        parent::tearDown();
    }

    public function test_it_generates_controller_file_in_correct_path(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function test_it_generates_controller_with_correct_namespace(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Controllers;',
            'class UserController',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function test_it_generates_nested_controller_in_correct_path(): void
    {
        $this->artisan('osdd:controller', ['name' => 'Admin/AdminController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/Admin/AdminController.php');
    }

    public function test_it_generates_nested_controller_with_correct_namespace(): void
    {
        $this->artisan('osdd:controller', ['name' => 'Admin/AdminController', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Controllers\Admin;',
            'class AdminController',
        ], 'functional/test-layer/src/Http/Controllers/Admin/AdminController.php');
    }

    public function test_it_generates_resource_controller(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'public function index()',
            'public function create()',
            'public function store(',
            'public function show(',
            'public function edit(',
            'public function update(',
            'public function destroy(',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function test_it_generates_resource_controller_with_form_requests(): void
    {
        $this->artisan('osdd:controller', ['name' => 'UserController', '--resource' => true, '--model' => 'User', '--requests' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/UpdateUserRequest.php');

        $this->assertFileContains([
            'use Functional\TestLayer\Http\Requests\StoreUserRequest;',
            'use Functional\TestLayer\Http\Requests\UpdateUserRequest;',
        ], 'functional/test-layer/src/Http/Controllers/UserController.php');
    }
}
