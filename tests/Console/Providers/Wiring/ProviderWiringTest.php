<?php

namespace Lomkit\Osdd\Tests\Console\Providers\Wiring;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Console\Providers\ProviderMethodEditor;
use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Console\Providers\Wiring\ProviderMethodMissing;
use Lomkit\Osdd\Console\Providers\Wiring\ProviderMissing;
use Lomkit\Osdd\Console\Providers\Wiring\ProviderWiring;
use Lomkit\Osdd\Console\Providers\Wiring\StatementAdded;
use Lomkit\Osdd\Console\Providers\Wiring\StatementAlreadyPresent;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\TestCase;

class ProviderWiringTest extends TestCase
{
    use UsesTemporaryProject;

    private const string PROVIDER = 'Functional\Orders\Providers\OrdersServiceProvider';

    private const string SEEDER = 'Functional\Orders\Database\Seeders\OrdersSeeder';

    private string $directory;

    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->directory = $this->project()->path('wiring');
        $this->files->ensureDirectoryExists($this->directory.'/src/Providers');
    }

    public function test_it_appends_the_statement_to_its_method(): void
    {
        $this->writeProvider("    public function boot(): void\n    {\n        //\n    }");

        $outcome = $this->wiring()->add($this->layer(), new LoadSeeders(self::SEEDER));

        $this->assertEquals(new StatementAdded(self::SEEDER, $this->providerPath()), $outcome);
        $this->assertSame(
            $this->providerSource("    public function boot(): void\n    {\n        \$this->loadSeeders([\\Functional\\Orders\\Database\\Seeders\\OrdersSeeder::class]);\n    }"),
            $this->files->get($this->providerPath()),
        );
    }

    public function test_it_references_a_class_the_provider_imports_by_its_short_name(): void
    {
        $this->writeProvider("    public function boot(): void\n    {\n        //\n    }", 'use '.self::SEEDER.";\n");

        $this->wiring()->add($this->layer(), new LoadSeeders(self::SEEDER));

        $this->assertStringContainsString("\n        \$this->loadSeeders([OrdersSeeder::class]);\n", $this->files->get($this->providerPath()));
    }

    public function test_it_leaves_the_provider_unchanged_when_the_call_is_already_made(): void
    {
        $this->writeProvider("    public function boot(): void\n    {\n        \$this->loadSeeders([OrdersSeeder::class]);\n    }", 'use '.self::SEEDER.";\n");
        $before = $this->files->get($this->providerPath());

        $outcome = $this->wiring()->add($this->layer(), new LoadSeeders(self::SEEDER));

        $this->assertInstanceOf(StatementAlreadyPresent::class, $outcome);
        $this->assertSame($before, $this->files->get($this->providerPath()));
    }

    public function test_it_returns_the_line_when_the_layer_has_no_provider(): void
    {
        $outcome = $this->wiring()->add($this->layer(), new OverrideConfig('payments.php'));

        $this->assertInstanceOf(ProviderMissing::class, $outcome);
        $this->assertSame('functional/orders', $outcome->layer->toString());
        $this->assertSame(ProviderMethod::Register, $outcome->method);
        $this->assertSame("\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');", $outcome->line);
    }

    public function test_it_returns_the_line_when_the_method_is_missing(): void
    {
        $this->writeProvider("    public function boot(): void\n    {\n        //\n    }");
        $before = $this->files->get($this->providerPath());

        $outcome = $this->wiring()->add($this->layer(), new OverrideConfig('payments.php'));

        $this->assertEquals(new ProviderMethodMissing(
            $this->providerPath(),
            ProviderMethod::Register,
            "\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');",
        ), $outcome);
        $this->assertSame($before, $this->files->get($this->providerPath()));
    }

    private function wiring(): ProviderWiring
    {
        return new ProviderWiring($this->files, new ProviderMethodEditor);
    }

    private function layer(): Layer
    {
        return new Layer(
            LayerName::fromString('functional/orders'),
            new LayerDirectory($this->directory),
            ['Functional\\Orders\\' => 'src/'],
            [],
            [self::PROVIDER],
            [],
            [],
        );
    }

    private function writeProvider(string $methods, string $imports = ''): void
    {
        $this->files->put($this->providerPath(), $this->providerSource($methods, $imports));
    }

    private function providerSource(string $methods, string $imports = ''): string
    {
        return "<?php\n\nnamespace Functional\\Orders\\Providers;\n\n{$imports}use Lomkit\\Osdd\\LayerServiceProvider;\n\nclass OrdersServiceProvider extends LayerServiceProvider\n{\n{$methods}\n}\n";
    }

    private function providerPath(): string
    {
        return $this->directory.'/src/Providers/OrdersServiceProvider.php';
    }
}
