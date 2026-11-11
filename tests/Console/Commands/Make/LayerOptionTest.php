<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Tests\Fixtures\TestbenchProcess;
use Lomkit\Osdd\Tests\TestCase;

class LayerOptionTest extends TestCase
{
    public function test_a_second_run_of_the_same_command_targets_its_own_layer(): void
    {
        $this->createTechnicalLayer('technical/other-layer');

        Artisan::call('osdd:enum', ['name' => 'First', '--layer' => 'functional/test-layer']);
        Artisan::call('osdd:enum', ['name' => 'Second', '--layer' => 'technical/other-layer']);

        $this->assertFileExists($this->enumPath('functional/test-layer', 'First'));
        $this->assertFileExists($this->enumPath('technical/other-layer', 'Second'));
        $this->assertFileDoesNotExist($this->enumPath('functional/test-layer', 'Second'));
    }

    public function test_it_accepts_the_package_name_of_a_layer(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->enumPath('functional/test-layer', 'Status'));
    }

    public function test_it_lists_the_candidates_of_an_ambiguous_package_name(): void
    {
        $this->createTechnicalLayer('technical/test-layer');

        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layer'])
            ->expectsOutputToContain('Layer [test-layer] is ambiguous: it matches functional/test-layer, technical/test-layer. Pass the full layer name.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->enumPath('functional/test-layer', 'Status'));
        $this->assertFileDoesNotExist($this->enumPath('technical/test-layer', 'Status'));
    }

    public function test_the_full_name_resolves_a_package_name_shared_by_two_layers(): void
    {
        $this->createTechnicalLayer('technical/test-layer');

        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'technical/test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->enumPath('technical/test-layer', 'Status'));
    }

    public function test_an_unknown_layer_lists_the_layers_and_suggests_the_closest(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layr'])
            ->expectsOutputToContain('Layer [test-layr] not found. Available layers: functional/test-layer. Did you mean [functional/test-layer]?')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->enumPath('functional/test-layer', 'Status'));
    }

    public function test_an_unknown_layer_far_from_every_name_gets_no_suggestion(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'functional/billing'])
            ->expectsOutputToContain('Layer [functional/billing] not found. Available layers: functional/test-layer.')
            ->doesntExpectOutputToContain('Did you mean')
            ->assertExitCode(1);
    }

    public function test_the_layer_option_is_required_without_interaction(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--no-interaction' => true])
            ->expectsOutputToContain('The --layer option is required when the command is not interactive. Available layers: functional/test-layer.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->enumPath('functional/test-layer', 'Status'));
    }

    public function test_the_layer_option_is_required_without_a_terminal(): void
    {
        $process = TestbenchProcess::run($this->project(), ['osdd:enum', 'Status']);

        $this->assertSame(1, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertStringContainsString('The --layer option is required when the command is not interactive.', $process->getOutput());
        $this->assertFileDoesNotExist($this->enumPath('functional/test-layer', 'Status'));
    }

    private function createTechnicalLayer(string $name): void
    {
        $this->layerFixture()->bucket('technical');
        $this->layerFixture()->layer($name, ['autoload' => ['psr-4' => ['Technical\\TestLayer\\' => 'src/']]]);
    }

    private function enumPath(string $layer, string $enum): string
    {
        return $this->project()->path("{$layer}/src/Enums/{$enum}.php");
    }
}
