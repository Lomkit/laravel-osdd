<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class RequestMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http'));

        parent::tearDown();
    }

    public function test_it_generates_request_file_in_correct_path(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function test_it_generates_request_with_correct_namespace(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Requests;',
            'class StoreUserRequest extends FormRequest',
        ], 'functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:request', ['name' => 'StoreUserRequest'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
    }

    public function test_it_generates_nested_request_in_correct_path(): void
    {
        $this->artisan('osdd:request', ['name' => 'Admin/StoreAdminRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/Admin/StoreAdminRequest.php');
    }

    public function test_it_generates_nested_request_with_correct_namespace(): void
    {
        $this->artisan('osdd:request', ['name' => 'Admin/StoreAdminRequest', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Http\Requests\Admin;',
            'class StoreAdminRequest extends FormRequest',
        ], 'functional/test-layer/src/Http/Requests/Admin/StoreAdminRequest.php');
    }
}
