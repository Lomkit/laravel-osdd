<?php

namespace Lomkit\Osdd\Tests\Scaffolding;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\LayerComposerManifest;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Scaffolding\LayerBlueprint;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\LayerScaffolder;
use Lomkit\Osdd\Scaffolding\LayerScaffolds;
use Lomkit\Osdd\Scaffolding\ScaffoldConsole;
use Lomkit\Osdd\Scaffolding\ScaffoldDependencyCycleException;
use Lomkit\Osdd\Seeders\SeederRegistry;
use Lomkit\Osdd\Tests\Scaffolding\Fixtures\KeyedScaffold;
use Lomkit\Osdd\Tests\Scaffolding\Fixtures\UnregisteredCommand;
use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class LayerScaffolderTest extends TestCase
{
    private string $projectPath;

    private BufferedOutput $output;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = sys_get_temp_dir().'/osdd-scaffolder-'.uniqid();
        mkdir($this->projectPath, 0755, true);

        $app->setBasePath($this->projectPath);
        $app['config']->set('osdd.layers.paths', ['functional' => $this->projectPath.'/functional']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->projectPath.'/composer.json', "{\n    \"require\": {}\n}\n");
        $this->output = new BufferedOutput;
        KeyedScaffold::$runs = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory($this->projectPath);
    }

    public function test_it_scaffolds_a_working_layer_without_a_console_command(): void
    {
        $this->scaffold('functional/scaffolded-orders', LayerScaffolds::standard()->parse(['routes', 'policy', 'model', 'seeder', 'provider']));

        $layer = $this->app->make(Layers::class)->find('functional/scaffolded-orders');
        $this->assertSame(['Functional\\ScaffoldedOrders\\Providers\\ScaffoldedOrdersServiceProvider'], $layer->providers);

        $root = $layer->directory->root;
        $this->assertFileExists($root.'/src/Models/ScaffoldedOrder.php');
        $this->assertFileExists($root.'/routes/web.php');
        $this->assertStringContainsString('use Functional\\ScaffoldedOrders\\Models\\ScaffoldedOrder;', (string) file_get_contents($root.'/src/Policies/ScaffoldedOrdersPolicy.php'));

        require_once $root.'/src/Providers/ScaffoldedOrdersServiceProvider.php';
        $this->app->register('Functional\\ScaffoldedOrders\\Providers\\ScaffoldedOrdersServiceProvider');
        $this->assertSame(['Functional\\ScaffoldedOrders\\Database\\Seeders\\ScaffoldedOrdersSeeder'], $this->app->make(SeederRegistry::class)->seeders());

        $composer = json_decode((string) file_get_contents($this->projectPath.'/composer.json'), true);
        $this->assertSame(['functional/scaffolded-orders' => '*'], $composer['require']);
        $this->assertSame([['type' => 'path', 'url' => './functional/*']], $composer['repositories']);

        $this->assertStringContainsString('Layer functional/scaffolded-orders created at functional/scaffolded-orders.', $this->output->fetch());
    }

    public function test_the_scaffolds_run_after_their_dependencies_and_see_the_whole_selection(): void
    {
        $this->scaffold('functional/keyed', [new KeyedScaffold('policy', ['model']), new KeyedScaffold('routes'), new KeyedScaffold('model')]);

        $this->assertSame(['model+model', 'policy+model', 'routes+model'], KeyedScaffold::$runs);
    }

    public function test_a_dependency_cycle_fails_before_anything_is_written(): void
    {
        try {
            $this->scaffold('functional/keyed', [new KeyedScaffold('model', ['policy']), new KeyedScaffold('policy', ['model'])]);
            $this->fail('The cycle was not detected.');
        } catch (ScaffoldDependencyCycleException) {
            $this->assertDirectoryDoesNotExist($this->projectPath.'/functional/keyed');
            $this->assertSame([], KeyedScaffold::$runs);
        }
    }

    public function test_without_a_root_composer_json_it_skips_the_registration(): void
    {
        unlink($this->projectPath.'/composer.json');

        $this->scaffold('functional/keyed', [new KeyedScaffold('routes')]);

        $this->assertFileExists($this->projectPath.'/functional/keyed/composer.json');
        $this->assertFileDoesNotExist($this->projectPath.'/composer.json');
        $this->assertStringContainsString('No composer.json found at project root, skipping Composer registration.', $this->output->fetch());
    }

    public function test_force_reaches_only_the_generators_that_take_it(): void
    {
        $scaffolds = LayerScaffolds::standard()->parse(['provider', 'migration', 'model']);
        $this->scaffold('functional/forced-orders', $scaffolds);

        $model = $this->projectPath.'/functional/forced-orders/src/Models/ForcedOrder.php';
        file_put_contents($model, "<?php // edited by hand\n");

        $this->scaffold('functional/forced-orders', $scaffolds, force: true);

        $this->assertStringContainsString('class ForcedOrder extends Model', (string) file_get_contents($model));
        $this->assertCount(1, (array) glob($this->projectPath.'/functional/forced-orders/database/migrations/*_create_forced_orders_table.php'));
        $this->assertStringContainsString('A create_forced_orders_table migration already exists, skipping the migration generator.', $this->output->fetch());
    }

    public function test_the_console_refuses_a_command_that_is_not_registered(): void
    {
        $this->expectException(CommandNotFoundException::class);
        $this->expectExceptionMessage('Command [osdd:unregistered] is not registered.');

        $this->console()->call(UnregisteredCommand::class, []);
    }

    /**
     * @param  list<LayerScaffold>  $scaffolds
     */
    private function scaffold(string $name, array $scaffolds, bool $force = false): void
    {
        $draft = LayerDraft::inBucket($this->projectPath.'/functional', LayerComposerManifest::for(LayerName::fromString($name)));

        $this->app->make(LayerScaffolder::class)->scaffold(new LayerBlueprint($draft, $scaffolds), $this->console(), $force);
    }

    private function console(): ScaffoldConsole
    {
        return new ScaffoldConsole($this->app->make(Kernel::class), new OutputStyle(new ArrayInput([]), $this->output), false);
    }
}
