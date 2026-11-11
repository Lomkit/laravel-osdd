<?php

namespace Lomkit\Osdd\Tests\Generation;

use Illuminate\Database\Console\Factories\FactoryMakeCommand;
use Illuminate\Foundation\Console\ListenerMakeCommand;
use Illuminate\Foundation\Console\ProviderMakeCommand;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Lomkit\Osdd\Generation\ControllerModelReplacements;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FrameworkTwinsTest extends TestCase
{
    private const string CONTROLLER_MODEL_REPLACEMENTS_SHA1 = '8c871e7975fa00900b56e70072a3ef02d3b7d898';

    public function test_controller_model_replacements_mirror_the_framework_method_that_asks_to_create_the_model(): void
    {
        $source = $this->sourceOf(ControllerMakeCommand::class, 'buildModelReplacements');

        $this->assertStringContainsString('confirm("A {$modelClass} model does not exist. Do you want to generate it?", default: true)', $source);
        $this->assertSame(
            self::CONTROLLER_MODEL_REPLACEMENTS_SHA1,
            sha1($source),
            'Laravel changed ControllerMakeCommand::buildModelReplacements(): update ControllerModelReplacements to match it, then this hash.',
        );

        foreach (array_keys(ControllerModelReplacements::of('Functional\\Orders\\Models\\Order')) as $placeholder) {
            $this->assertStringContainsString("'{$placeholder}' =>", $source);
        }
    }

    public function test_controller_form_requests_still_use_the_hard_coded_framework_namespace(): void
    {
        $this->assertStringContainsString(
            "\$namespace = 'App\\\\Http\\\\Requests';",
            $this->sourceOf(ControllerMakeCommand::class, 'buildFormRequestReplacements'),
            'Laravel no longer hard-codes App\\Http\\Requests: drop the namespace remapping of the osdd:controller generator.',
        );
    }

    public function test_the_framework_listener_still_reads_the_application_namespace(): void
    {
        $this->assertStringContainsString(
            '$this->laravel->getNamespace()',
            $this->sourceOf(ListenerMakeCommand::class, 'buildClass'),
            'Laravel no longer reads the application namespace in ListenerMakeCommand::buildClass(): let the osdd:listener generator call it.',
        );
    }

    public function test_the_framework_provider_generator_still_registers_the_provider_in_the_bootstrap_file(): void
    {
        $this->assertStringContainsString(
            'ServiceProvider::addProviderToBootstrapFile(',
            $this->sourceOf(ProviderMakeCommand::class, 'handle'),
            'Laravel no longer adds the generated provider to bootstrap/providers.php: let the osdd:provider generator call ProviderMakeCommand::handle().',
        );
    }

    public function test_the_framework_factory_namespace_is_a_stub_placeholder(): void
    {
        $this->assertStringContainsString("'{{ factoryNamespace }}' => \$namespace", $this->sourceOf(FactoryMakeCommand::class, 'buildClass'));
    }

    /**
     * @param  class-string  $class
     */
    private function sourceOf(string $class, string $method): string
    {
        $reflection = new ReflectionMethod($class, $method);
        $lines = file((string) $reflection->getFileName()) ?: [];
        $start = (int) $reflection->getStartLine();

        return (string) preg_replace('/\s+/', ' ', trim(implode('', array_slice($lines, $start - 1, (int) $reflection->getEndLine() - $start + 1))));
    }
}
