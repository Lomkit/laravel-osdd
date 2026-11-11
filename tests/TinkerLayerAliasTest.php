<?php

namespace Lomkit\Osdd\Tests;

use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;

class TinkerLayerAliasTest extends TestCase
{
    private string $tmpPath;

    /**
     * @var list<callable>
     */
    private array $autoloadersBeforeBoot = [];

    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $_SERVER['argv'][] = 'tinker';
        $this->autoloadersBeforeBoot = spl_autoload_functions();

        $this->tmpPath = $this->project()->root;

        mkdir($this->tmpPath.'/functional/billing/src', 0755, true);

        file_put_contents($this->tmpPath.'/functional/billing/composer.json', json_encode([
            'name' => 'functional/billing',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\Billing\\' => 'src/']],
        ]));

        file_put_contents(
            $this->tmpPath.'/functional/billing/src/Invoice.php',
            "<?php\nnamespace Functional\\Billing;\nclass Invoice {}"
        );

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->tmpPath.'/functional',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (spl_autoload_functions() as $autoloader) {
            if (! in_array($autoloader, $this->autoloadersBeforeBoot, true)) {
                spl_autoload_unregister($autoloader);
            }
        }

        $_SERVER['argv'] = array_filter($_SERVER['argv'], fn ($v) => $v !== 'tinker');
    }

    public function test_layer_class_is_resolvable_by_short_name(): void
    {
        class_exists('Invoice');

        $this->assertTrue(class_exists('Invoice'));
    }

    public function test_layer_class_short_name_resolves_to_correct_fqcn(): void
    {
        class_exists('Invoice');

        $this->assertSame('Functional\\Billing\\Invoice', (new \ReflectionClass('Invoice'))->getName());
    }

    public function test_layer_namespaces_are_added_to_tinker_alias(): void
    {
        $this->assertContains('Functional\\Billing\\', $this->app['config']->get('tinker.alias', []));
    }
}
