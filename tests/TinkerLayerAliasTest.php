<?php

namespace Lomkit\Osdd\Tests;

use Illuminate\Filesystem\Filesystem;

class TinkerLayerAliasTest extends TestCase
{
    private string $tmpPath;

    protected function getEnvironmentSetUp($app): void
    {
        $_SERVER['argv'][] = 'tinker';

        $this->tmpPath = sys_get_temp_dir().'/osdd-tinker-test-'.uniqid();

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

        $app->setBasePath($this->tmpPath);
        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->tmpPath.'/functional',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->tmpPath);

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
