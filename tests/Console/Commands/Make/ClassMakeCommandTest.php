<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

class ClassMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->delete($this->app->basePath('functional/test-layer/src/PaymentService.php'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Services'));

        parent::tearDown();
    }

    public function test_it_generates_class_file_in_correct_path(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/PaymentService.php');
    }

    public function test_it_generates_class_with_correct_namespace(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer;',
            'class PaymentService',
        ], 'functional/test-layer/src/PaymentService.php');
    }

    public function test_it_prompts_for_layer_when_not_provided(): void
    {
        $this->artisan('osdd:class', ['name' => 'PaymentService'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/PaymentService.php');
    }

    public function test_it_generates_nested_class_in_correct_path(): void
    {
        $this->artisan('osdd:class', ['name' => 'Services/PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Services/PaymentService.php');
    }

    public function test_it_generates_nested_class_with_correct_namespace(): void
    {
        $this->artisan('osdd:class', ['name' => 'Services/PaymentService', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Services;',
            'class PaymentService',
        ], 'functional/test-layer/src/Services/PaymentService.php');
    }
}
