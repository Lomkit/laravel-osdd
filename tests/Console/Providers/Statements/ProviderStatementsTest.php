<?php

namespace Lomkit\Osdd\Tests\Console\Providers\Statements;

use Lomkit\Osdd\Console\Providers\Calls\ProviderFile;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Console\Providers\Source\ProviderSource;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;
use Lomkit\Osdd\Console\Providers\Statements\RegisterCommand;
use Lomkit\Osdd\Console\Providers\Statements\WithComponents;
use Lomkit\Osdd\Console\Providers\Statements\WithTranslations;
use Lomkit\Osdd\Console\Providers\Statements\WithViews;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProviderStatementsTest extends TestCase
{
    private const string LAYER = '/project/functional/orders';

    /**
     * @return iterable<string, array{ProviderStatement, ProviderMethod, string, string}>
     */
    public static function statements(): iterable
    {
        $default = LayerResourceNamespace::of(LayerName::fromString('functional/orders'), new Layers([]));
        $shared = LayerResourceNamespace::of(
            LayerName::fromString('technical/orders'),
            new Layers([self::layerNamed('functional/orders'), self::layerNamed('technical/orders')]),
        );

        yield 'migrations' => [new LoadMigrations, ProviderMethod::Boot, "\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');", 'database/migrations'];
        yield 'seeder' => [new LoadSeeders('Functional\Orders\Database\Seeders\OrdersSeeder'), ProviderMethod::Boot, '$this->loadSeeders([\Functional\Orders\Database\Seeders\OrdersSeeder::class]);', 'Functional\Orders\Database\Seeders\OrdersSeeder'];
        yield 'command' => [new RegisterCommand('\Functional\Orders\Console\Commands\CloseDay'), ProviderMethod::Boot, '$this->commands([\Functional\Orders\Console\Commands\CloseDay::class]);', 'Functional\Orders\Console\Commands\CloseDay'];
        yield 'config' => [new OverrideConfig('payments'), ProviderMethod::Register, "\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');", 'config:payments'];
        yield 'views' => [new WithViews($default), ProviderMethod::Boot, "\$this->withViews(__DIR__ . '/../../resources/views');", 'resources/views'];
        yield 'views of a shared package name' => [new WithViews($shared), ProviderMethod::Boot, "\$this->withViews(__DIR__ . '/../../resources/views', 'technical-orders');", 'resources/views'];
        yield 'translations' => [new WithTranslations($default), ProviderMethod::Boot, "\$this->withTranslations(__DIR__ . '/../../lang');", 'lang'];
        yield 'translations of a shared package name' => [new WithTranslations($shared), ProviderMethod::Boot, "\$this->withTranslations(__DIR__ . '/../../lang', 'technical-orders');", 'lang'];
        yield 'components' => [new WithComponents('Functional\Orders\View\Components\\', $default), ProviderMethod::Boot, "\$this->withComponents('Functional\\\\Orders\\\\View\\\\Components');", 'Functional\Orders\View\Components'];
        yield 'components of a shared package name' => [new WithComponents('Technical\Orders\View\Components', $shared), ProviderMethod::Boot, "\$this->withComponents('Technical\\\\Orders\\\\View\\\\Components', 'technical-orders');", 'Technical\Orders\View\Components'];
    }

    #[DataProvider('statements')]
    public function test_a_statement_renders_one_call_for_its_method(ProviderStatement $statement, ProviderMethod $method, string $line, string $label): void
    {
        $this->assertSame($method, $statement->method());
        $this->assertSame($line, $statement->call()->render(ProviderFile::withoutImports($this->provider('src/Providers/OrdersServiceProvider.php'))));
        $this->assertSame($label, $statement->label());
    }

    #[DataProvider('statements')]
    public function test_a_rendered_statement_is_present_in_a_provider_that_contains_it(ProviderStatement $statement, ProviderMethod $method, string $line, string $label): void
    {
        $provider = $this->provider('src/Providers/OrdersServiceProvider.php');

        $this->assertTrue(ProviderSource::parse("<?php\n\nclass OrdersServiceProvider\n{\n    public function boot(): void\n    {\n        {$line}\n    }\n}\n")->contains($statement->call(), $provider));
        $this->assertFalse(ProviderSource::parse("<?php\n\nclass OrdersServiceProvider\n{\n}\n")->contains($statement->call(), $provider));
    }

    public function test_paths_are_relative_to_the_provider_file(): void
    {
        $line = (new LoadMigrations)->call()->render(ProviderFile::withoutImports($this->provider('src/Support/Boot/OrdersServiceProvider.php')));

        $this->assertSame("\$this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');", $line);
    }

    public function test_a_class_the_provider_imports_is_referenced_by_its_alias(): void
    {
        $imports = ProviderSource::parse("<?php\n\nnamespace Functional\\Orders\\Providers;\n\nuse Functional\\Orders\\Database\\Seeders\\OrdersSeeder as Seeder;\n")->imports;
        $file = new ProviderFile($this->provider('src/Providers/OrdersServiceProvider.php'), $imports);

        $this->assertSame('$this->loadSeeders([Seeder::class]);', (new LoadSeeders('Functional\Orders\Database\Seeders\OrdersSeeder'))->call()->render($file));
        $this->assertSame('$this->commands([\Functional\Orders\Console\Commands\CloseDay::class]);', (new RegisterCommand('Functional\Orders\Console\Commands\CloseDay'))->call()->render($file));
    }

    public function test_the_config_key_is_the_file_name_without_extension(): void
    {
        $this->assertSame(
            "\$this->overrideConfigFrom(__DIR__ . '/../../config/billing/payments.php', 'billing/payments');",
            (new OverrideConfig('billing/payments.php'))->call()->render(ProviderFile::withoutImports($this->provider('src/Providers/OrdersServiceProvider.php'))),
        );
    }

    private function provider(string $relativePath): LayerProvider
    {
        $directory = new LayerDirectory(self::LAYER);

        return LayerProvider::at($directory, $directory->pathTo($relativePath));
    }

    private static function layerNamed(string $name): Layer
    {
        return new Layer(LayerName::fromString($name), new LayerDirectory('/project/'.$name), [], [], [], [], []);
    }
}
