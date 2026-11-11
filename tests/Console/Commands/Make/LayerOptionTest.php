<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Process\Process;

class LayerOptionTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Enums'));
        $this->app['files']->deleteDirectory($this->app->basePath('technical'));

        parent::tearDown();
    }

    public function test_a_second_run_of_the_same_command_targets_its_own_layer(): void
    {
        $this->createTechnicalLayer('technical/other-layer');

        Artisan::call('osdd:enum', ['name' => 'First', '--layer' => 'functional/test-layer']);
        Artisan::call('osdd:enum', ['name' => 'Second', '--layer' => 'technical/other-layer']);

        $this->assertFileExists($this->app->basePath('functional/test-layer/src/Enums/First.php'));
        $this->assertFileExists($this->app->basePath('technical/other-layer/src/Enums/Second.php'));
        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Enums/Second.php'));
    }

    public function test_it_accepts_the_package_name_of_a_layer(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('functional/test-layer/src/Enums/Status.php'));
    }

    public function test_it_lists_the_candidates_of_an_ambiguous_package_name(): void
    {
        $this->createTechnicalLayer('technical/test-layer');

        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layer'])
            ->expectsOutputToContain('Layer [test-layer] is ambiguous: it matches functional/test-layer, technical/test-layer. Pass the full layer name.')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Enums/Status.php'));
        $this->assertFileDoesNotExist($this->app->basePath('technical/test-layer/src/Enums/Status.php'));
    }

    public function test_the_full_name_resolves_a_package_name_shared_by_two_layers(): void
    {
        $this->createTechnicalLayer('technical/test-layer');

        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'technical/test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('technical/test-layer/src/Enums/Status.php'));
    }

    public function test_an_unknown_layer_lists_the_layers_and_suggests_the_closest(): void
    {
        $this->artisan('osdd:enum', ['name' => 'Status', '--layer' => 'test-layr'])
            ->expectsOutputToContain('Layer [test-layr] not found. Available layers: functional/test-layer. Did you mean [functional/test-layer]?')
            ->assertExitCode(1);

        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Enums/Status.php'));
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

        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Enums/Status.php'));
    }

    public function test_the_layer_option_is_required_without_a_terminal(): void
    {
        $process = new Process([PHP_BINARY, 'vendor/bin/testbench', 'osdd:enum', 'Status'], dirname(__DIR__, 4));
        $process->setInput('');
        $process->run();

        $this->assertSame(1, $process->getExitCode(), $process->getOutput().$process->getErrorOutput());
        $this->assertStringContainsString('The --layer option is required when the command is not interactive.', $process->getOutput());
        $this->assertFileDoesNotExist($this->app->basePath('functional/test-layer/src/Enums/Status.php'));
    }

    private function createTechnicalLayer(string $name): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->app['files']->ensureDirectoryExists($this->app->basePath($name.'/src'));
        $this->app['files']->put($this->app->basePath($name.'/composer.json'), json_encode([
            'name' => $name,
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Technical\\TestLayer\\' => 'src/']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
