<?php

namespace Lomkit\Osdd\Tests\Console\Providers\Source;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\Source\ProviderSource;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;
use Lomkit\Osdd\Console\Providers\Statements\RegisterCommand;
use Lomkit\Osdd\Console\Providers\Statements\WithComponents;
use Lomkit\Osdd\Console\Providers\Statements\WithViews;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProviderSourceTest extends TestCase
{
    private const string SEEDER = 'Functional\Orders\Database\Seeders\OrdersSeeder';

    /**
     * @return iterable<string, array{string, ProviderStatement}>
     */
    public static function presentCalls(): iterable
    {
        yield 'formatted without spaces around the dot' => ["\$this->loadMigrationsFrom(__DIR__.'/../../database/migrations');", new LoadMigrations];
        yield 'split over several lines' => ["\$this->loadMigrationsFrom(\n            __DIR__ . '/../../database/migrations',\n        );", new LoadMigrations];
        yield 'with a comment inside' => ["\$this->loadMigrationsFrom(/* layer */ __DIR__ . '/../../database/migrations');", new LoadMigrations];
        yield 'inside a condition' => ["if (\$this->app->runningInConsole()) {\n            \$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');\n        }", new LoadMigrations];
        yield 'in another method' => ["\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');", new OverrideConfig('payments.php')];
        yield 'an imported class' => ['$this->loadSeeders([OrdersSeeder::class]);', new LoadSeeders(self::SEEDER)];
        yield 'an aliased class' => ['$this->loadSeeders([Seeder::class]);', new LoadSeeders(self::SEEDER)];
        yield 'a class of a group import' => ['$this->commands([CloseDay::class]);', new RegisterCommand('Functional\Orders\Console\Commands\CloseDay')];
        yield 'a class relative to an imported namespace' => ['$this->loadSeeders([Seeders\OrdersSeeder::class]);', new LoadSeeders(self::SEEDER)];
        yield 'a class of the provider namespace' => ['$this->commands([Commands\Prune::class]);', new RegisterCommand('Functional\Orders\Providers\Commands\Prune')];
        yield 'a trailing comma in the list' => ["\$this->loadSeeders([\n            \\Functional\\Orders\\Database\\Seeders\\OrdersSeeder::class,\n        ]);", new LoadSeeders(self::SEEDER)];
        yield 'views with another namespace' => ["\$this->withViews(__DIR__ . '/../../resources/views', 'shop');", new WithViews(self::resourceNamespace())];
        yield 'components of another namespace' => ["\$this->withComponents('Shop\\\\Components');", new WithComponents('Functional\Orders\View\Components', self::resourceNamespace())];
        yield 'a helper name in another case' => ['$this->LoadSeeders([\Functional\Orders\Database\Seeders\OrdersSeeder::class]);', new LoadSeeders(self::SEEDER)];
    }

    /**
     * @return iterable<string, array{string, ProviderStatement}>
     */
    public static function absentCalls(): iterable
    {
        yield 'a commented-out call' => ["// \$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');", new LoadMigrations];
        yield 'a call in a string' => ["\$line = '\$this->loadMigrationsFrom(__DIR__ . \\'/../../database/migrations\\');';", new LoadMigrations];
        yield 'another folder' => ["\$this->loadMigrationsFrom(__DIR__ . '/../../database/archive');", new LoadMigrations];
        yield 'another seeder' => ['$this->loadSeeders([\Functional\Orders\Database\Seeders\RefundsSeeder::class]);', new LoadSeeders(self::SEEDER)];
        yield 'a class of the same short name in another namespace' => ['$this->commands([Commands\CloseDay::class]);', new RegisterCommand('Functional\Orders\Console\Commands\CloseDay')];
        yield 'another config key' => ["\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'billing');", new OverrideConfig('payments.php')];
        yield 'a call on another object' => ["\$this->app->loadMigrationsFrom(__DIR__ . '/../../database/migrations');", new LoadMigrations];
    }

    #[DataProvider('presentCalls')]
    public function test_a_call_already_made_is_present(string $statementLine, ProviderStatement $statement): void
    {
        $this->assertTrue($this->source($statementLine)->contains($statement->call(), $this->provider()));
    }

    #[DataProvider('absentCalls')]
    public function test_a_call_not_made_is_absent(string $statementLine, ProviderStatement $statement): void
    {
        $this->assertFalse($this->source($statementLine)->contains($statement->call(), $this->provider()));
    }

    public function test_it_keeps_the_source_code(): void
    {
        $code = "<?php\n\nclass OrdersServiceProvider {}\n";

        $this->assertSame($code, ProviderSource::parse($code)->code);
    }

    public function test_it_reads_imports_declared_outside_the_class_only(): void
    {
        $source = ProviderSource::parse(<<<'PHP'
            <?php

            namespace Functional\Orders\Providers;

            use Functional\Orders\Database\Seeders\OrdersSeeder;
            use function Functional\Orders\helper;
            use const Functional\Orders\VERSION;

            class OrdersServiceProvider
            {
                use Functional\Orders\Concerns\Boots;
            }
            PHP);

        $this->assertSame('OrdersSeeder', $source->imports->aliasOf(self::SEEDER));
        $this->assertNull($source->imports->aliasOf('Functional\Orders\Concerns\Boots'));
        $this->assertNull($source->imports->aliasOf('Functional\Orders\helper'));
        $this->assertSame('\Functional\Orders\Providers\Boots', $source->imports->resolve('Boots'));
    }

    private function source(string $statementLine): ProviderSource
    {
        return ProviderSource::parse(<<<PHP
            <?php

            namespace Functional\Orders\Providers;

            use Functional\Orders\Console\Commands\{CloseDay, Archive};
            use Functional\Orders\Database\Seeders;
            use Functional\Orders\Database\Seeders\OrdersSeeder, Functional\Orders\Database\Seeders\OrdersSeeder as Seeder;
            use Lomkit\Osdd\LayerServiceProvider;

            class OrdersServiceProvider extends LayerServiceProvider
            {
                public function boot(): void
                {
                    {$statementLine}
                }
            }
            PHP);
    }

    private function provider(): LayerProvider
    {
        $directory = new LayerDirectory('/project/functional/orders');

        return LayerProvider::at($directory, $directory->pathTo('src/Providers/OrdersServiceProvider.php'));
    }

    private static function resourceNamespace(): LayerResourceNamespace
    {
        return LayerResourceNamespace::of(LayerName::fromString('functional/orders'), new Layers([]));
    }
}
