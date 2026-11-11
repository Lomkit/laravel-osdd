<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Process\Process;

class ExistingModelTest extends TestCase
{
    private const string QUESTION = 'Do you want to generate additional components for the model?';

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Models'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/factories'));

        parent::tearDown();
    }

    public function test_it_asks_before_adding_components_to_an_existing_model(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer', '--factory' => true])
            ->expectsOutputToContain('Model already exists.')
            ->expectsConfirmation(self::QUESTION, 'no')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/database/factories/OrderFactory.php'));
    }

    public function test_it_adds_the_requested_components_to_an_existing_model_when_confirmed(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer', '--factory' => true])
            ->expectsConfirmation(self::QUESTION, 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('functional/test-layer/database/factories/OrderFactory.php'));
    }

    public function test_without_a_terminal_it_adds_the_requested_components_to_an_existing_model(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $process = new Process([PHP_BINARY, 'vendor/bin/testbench', 'osdd:model', 'Order', '--layer=functional/test-layer', '--factory'], dirname(__DIR__, 4));
        $process->setInput('');
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertStringContainsString('Model already exists.', $process->getOutput());
        $this->assertFileExists($this->app->basePath('functional/test-layer/database/factories/OrderFactory.php'));
    }
}
