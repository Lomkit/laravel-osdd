<?php

namespace Lomkit\Osdd\Tests\Generation;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Generation\GeneratesInLayer;
use Lomkit\Osdd\Generation\GenerationTargetNotResolvedException;
use Lomkit\Osdd\Tests\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

class GeneratesInLayerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Enums'));
        $this->app['files']->deleteDirectory($this->app->basePath('technical'));

        parent::tearDown();
    }

    public function test_every_generator_is_a_final_class_on_the_adapter(): void
    {
        $generators = $this->generators();

        $this->assertCount(37, $generators);

        foreach ($generators as $name => $command) {
            $class = new ReflectionClass($command);

            $this->assertTrue($class->isFinal(), "{$name} is not final.");
            $this->assertContains(GeneratesInLayer::class, class_uses_recursive($command), "{$name} does not use GeneratesInLayer.");
        }
    }

    public function test_a_command_instance_has_no_target_outside_of_a_run(): void
    {
        $command = $this->generators()['osdd:enum'];

        Artisan::call('osdd:enum', ['name' => 'Status', '--layer' => 'functional/test-layer']);

        $this->assertSame($command, $this->generators()['osdd:enum']);
        $this->expectException(GenerationTargetNotResolvedException::class);

        (new ReflectionMethod($command, 'target'))->invoke($command);
    }

    public function test_a_second_run_does_not_reuse_the_resource_namespace_of_the_first(): void
    {
        $this->createTechnicalLayer('technical/test-layer', 'Technical\\TestLayer\\');
        $this->createTechnicalLayer('technical/other-layer', 'Technical\\OtherLayer\\');
        $command = $this->generators()['osdd:view'];

        Artisan::call('osdd:view', ['name' => 'first', '--layer' => 'technical/test-layer']);
        $first = Artisan::output();
        Artisan::call('osdd:view', ['name' => 'second', '--layer' => 'technical/other-layer']);
        $second = Artisan::output();

        $this->assertSame($command, $this->generators()['osdd:view']);
        $this->assertStringContainsString('Another layer is also named [test-layer]', $first);
        $this->assertStringContainsString("'technical-test-layer'", $first);
        $this->assertStringNotContainsString('Another layer is also named', $second);
        $this->assertStringNotContainsString('technical-test-layer', $second);
        $this->assertFileExists($this->app->basePath('technical/other-layer/resources/views/second.blade.php'));
        $this->assertFileDoesNotExist($this->app->basePath('technical/test-layer/resources/views/second.blade.php'));
    }

    public function test_the_migration_generator_has_no_path_options(): void
    {
        $definition = $this->generators()['osdd:migration']->getDefinition();

        $this->assertFalse($definition->hasOption('path'));
        $this->assertFalse($definition->hasOption('realpath'));
        $this->assertTrue($definition->hasOption('layer'));
    }

    /**
     * @return array<string, Command>
     */
    private function generators(): array
    {
        return array_filter(
            $this->app->make(Kernel::class)->all(),
            fn (Command $command, string $name): bool => str_starts_with($name, 'osdd:') && $command->getDefinition()->hasOption('layer') && ! in_array($name, ['osdd:seed', 'osdd:check', 'osdd:phpunit'], true),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private function createTechnicalLayer(string $name, string $namespace): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->app['files']->ensureDirectoryExists($this->app->basePath($name.'/src'));
        $this->app['files']->put($this->app->basePath($name.'/composer.json'), json_encode([
            'name' => $name,
            'type' => 'layer',
            'autoload' => ['psr-4' => [$namespace => 'src/']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
