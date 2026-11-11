<?php

namespace Lomkit\Osdd\Tests\Resources;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\LayerManifestNotFoundException;
use Lomkit\Osdd\Resources\ResourceNamespaceCollisionException;
use Lomkit\Osdd\Tests\Concerns\CompilesViews;
use Lomkit\Osdd\Tests\Concerns\CreatesLayers;
use Lomkit\Osdd\Tests\TestCase;

class LayerResourcesTest extends TestCase
{
    use CompilesViews;
    use CreatesLayers;

    protected function tearDown(): void
    {
        $this->deleteCreatedLayers();

        parent::tearDown();
    }

    public function test_views_use_the_package_name_as_default_namespace(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        $this->app['files']->put($layer.'/resources/views/hello.blade.php', 'Hello {{ $name }}');

        $this->registerLayerProvider($this->createLayerProvider($layer, 'Functional\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views');"));

        $this->assertSame('Hello Ada', view('receipts::hello', ['name' => 'Ada'])->render());
    }

    public function test_views_accept_an_explicit_namespace(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        $this->app['files']->put($layer.'/resources/views/hello.blade.php', 'Hello');

        $this->registerLayerProvider($this->createLayerProvider($layer, 'Functional\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views', 'billing-receipts');"));

        $this->assertSame('Hello', view('billing-receipts::hello')->render());
        $this->assertArrayNotHasKey('receipts', $this->app['view']->getFinder()->getHints());
    }

    public function test_a_missing_views_directory_registers_nothing(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');

        $this->registerLayerProvider($this->createLayerProvider($layer, 'Functional\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views');"));

        $this->assertArrayNotHasKey('receipts', $this->app['view']->getFinder()->getHints());
    }

    public function test_translations_load_namespaced_and_json_files(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $this->app['files']->ensureDirectoryExists($layer.'/lang/en');
        $this->app['files']->put($layer.'/lang/en/messages.php', "<?php\n\nreturn ['welcome' => 'Welcome to receipts'];\n");
        $this->app['files']->put($layer.'/lang/fr.json', '{"Download the receipt": "Télécharger le reçu"}');

        $this->registerLayerProvider($this->createLayerProvider($layer, 'Functional\\Receipts', "        \$this->withTranslations(__DIR__ . '/../../lang');"));

        $this->assertSame('Welcome to receipts', __('receipts::messages.welcome'));
        $this->assertSame('Télécharger le reçu', __('Download the receipt', locale: 'fr'));
    }

    public function test_class_components_render_under_the_package_prefix(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $componentNamespace = 'Functional\\Receipts'.Str::random(8).'\\View\\Components';
        $this->writeComponent($layer, $componentNamespace, 'Badge', '<span class="badge">Paid</span>');

        $this->registerLayerProvider($this->createLayerProvider(
            $layer,
            'Functional\\Receipts',
            "        \$this->withComponents('".str_replace('\\', '\\\\', $componentNamespace)."');",
        ));

        $this->assertSame('<span class="badge">Paid</span>', trim(Blade::render('<x-receipts::badge />')));
    }

    public function test_components_accept_an_explicit_prefix(): void
    {
        $layer = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $componentNamespace = 'Functional\\Receipts'.Str::random(8).'\\View\\Components';
        $this->writeComponent($layer, $componentNamespace, 'Badge', '<span>Paid</span>');

        $this->registerLayerProvider($this->createLayerProvider(
            $layer,
            'Functional\\Receipts',
            "        \$this->withComponents('".str_replace('\\', '\\\\', $componentNamespace)."', 'billing');",
        ));

        $this->assertSame('<span>Paid</span>', trim(Blade::render('<x-billing::badge />')));
    }

    public function test_two_layers_with_the_same_package_name_collide_on_the_default_namespace(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();
        $first = $this->createLayerProvider($functional, 'Functional\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views');");
        $second = $this->createLayerProvider($technical, 'Technical\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views');");

        $this->registerLayerProvider($first);

        try {
            $this->registerLayerProvider($second);
            $this->fail('The second provider registered the same views namespace.');
        } catch (ResourceNamespaceCollisionException $exception) {
            $this->assertSame(
                "The views namespace [receipts] is registered by both [{$first['class']}] and [{$second['class']}]. Pass another namespace as the second argument of withViews() in one of them.",
                $exception->getMessage(),
            );
        }
    }

    public function test_an_explicit_namespace_resolves_the_collision(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();
        $this->app['files']->put($functional.'/resources/views/title.blade.php', 'Functional');
        $this->app['files']->put($technical.'/resources/views/title.blade.php', 'Technical');

        $this->registerLayerProvider($this->createLayerProvider($functional, 'Functional\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views');"));
        $this->registerLayerProvider($this->createLayerProvider($technical, 'Technical\\Receipts', "        \$this->withViews(__DIR__ . '/../../resources/views', 'technical-receipts');"));

        $this->assertSame('Functional', view('receipts::title')->render());
        $this->assertSame('Technical', view('technical-receipts::title')->render());
    }

    public function test_component_prefixes_collide_like_view_namespaces(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();

        $this->registerLayerProvider($this->createLayerProvider($functional, 'Functional\\Receipts', "        \$this->withComponents('Functional\\\\Receipts\\\\View\\\\Components');"));

        $this->expectException(ResourceNamespaceCollisionException::class);
        $this->expectExceptionMessage('The components prefix [receipts]');

        $this->registerLayerProvider($this->createLayerProvider($technical, 'Technical\\Receipts', "        \$this->withComponents('Technical\\\\Receipts\\\\View\\\\Components');"));
    }

    public function test_a_default_namespace_needs_a_composer_json_above_the_provider(): void
    {
        $directory = sys_get_temp_dir().'/osdd-no-manifest-'.Str::random(8);
        $this->createdLayerPaths[] = $directory;
        $provider = $this->createLayerProvider($directory, 'Functional\\Orphan', "        \$this->withComponents('Functional\\\\Orphan\\\\View\\\\Components');");

        $this->expectException(LayerManifestNotFoundException::class);
        $this->expectExceptionMessage("No composer.json found above [{$provider['path']}]");

        $this->registerLayerProvider($provider);
    }

    /**
     * @return array{string, string}
     */
    private function createTwoReceiptsLayers(): array
    {
        $functional = $this->createLayer($this->app->basePath('functional/receipts'), 'functional/receipts');
        $technical = $this->createLayer($this->app->basePath('technical/receipts'), 'technical/receipts');
        $this->createdLayerPaths[] = $this->app->basePath('technical');

        foreach ([$functional, $technical] as $layer) {
            $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        }

        return [$functional, $technical];
    }

    private function writeComponent(string $layer, string $namespace, string $class, string $html): void
    {
        $path = $layer.'/src/View/Components/'.Str::random(8).'/'.$class.'.php';
        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, "<?php\n\nnamespace {$namespace};\n\nuse Illuminate\\View\\Component;\n\nclass {$class} extends Component\n{\n    public function render(): string\n    {\n        return '{$html}';\n    }\n}\n");

        require_once $path;
    }
}
