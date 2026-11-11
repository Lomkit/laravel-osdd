<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class PolicyMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Policies'));

        parent::tearDown();
    }

    public function test_it_generates_policy_file_in_correct_path(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function test_it_generates_policy_with_correct_namespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Policies;',
            'class UserPolicy',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function test_it_generates_nested_policy_in_correct_path(): void
    {
        $this->artisan('osdd:policy', ['name' => 'Admin/AdminPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Policies/Admin/AdminPolicy.php');
    }

    public function test_it_generates_nested_policy_with_correct_namespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'Admin/AdminPolicy', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Policies\Admin;',
            'class AdminPolicy',
        ], 'functional/test-layer/src/Policies/Admin/AdminPolicy.php');
    }

    public function test_it_generates_policy_with_model_methods(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'public function viewAny(',
            'public function view(',
            'public function create(',
            'public function update(',
            'public function delete(',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function test_it_qualifies_model_with_models_sub_namespace(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use Functional\TestLayer\Models\User;',
        ], 'functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function test_it_replaces_user_model_placeholder_in_policy_stub(): void
    {
        $this->artisan('osdd:policy', ['name' => 'UserPolicy', '--layer' => 'functional/test-layer', '--model' => 'User'])
            ->assertExitCode(0);

        $contents = $this->app['files']->get(
            $this->app->basePath('functional/test-layer/src/Policies/UserPolicy.php')
        );

        $this->assertStringNotContainsString('{{ namespacedUserModel }}', $contents);
        $this->assertStringNotContainsString('{{namespacedUserModel}}', $contents);
    }
}
