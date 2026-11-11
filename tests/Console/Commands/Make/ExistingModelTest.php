<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\TestbenchProcess;
use Lomkit\Osdd\Tests\TestCase;

class ExistingModelTest extends TestCase
{
    private const string QUESTION = 'Do you want to generate additional components for the model?';

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer'])->assertExitCode(0);
    }

    public function test_it_asks_before_adding_components_to_an_existing_model(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer', '--factory' => true])
            ->expectsOutputToContain('Model already exists.')
            ->expectsConfirmation(self::QUESTION, 'no')
            ->assertExitCode(0);

        $this->assertFileDoesNotExist($this->factoryPath());
    }

    public function test_it_adds_the_requested_components_to_an_existing_model_when_confirmed(): void
    {
        $this->artisan('osdd:model', ['name' => 'Order', '--layer' => 'functional/test-layer', '--factory' => true])
            ->expectsConfirmation(self::QUESTION, 'yes')
            ->assertExitCode(0);

        $this->assertFileExists($this->factoryPath());
    }

    public function test_without_a_terminal_it_adds_the_requested_components_to_an_existing_model(): void
    {
        $process = TestbenchProcess::run($this->project(), ['osdd:model', 'Order', '--layer=functional/test-layer', '--factory']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertStringContainsString('Model already exists.', $process->getOutput());
        $this->assertFileExists($this->factoryPath());
    }

    private function factoryPath(): string
    {
        return $this->project()->path('functional/test-layer/database/factories/OrderFactory.php');
    }
}
