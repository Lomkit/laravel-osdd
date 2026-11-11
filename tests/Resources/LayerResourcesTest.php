<?php

namespace Lomkit\Osdd\Tests\Resources;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Lomkit\Osdd\Layers\ProviderLayerNotFoundException;
use Lomkit\Osdd\Resources\ResourceNamespaceCollisionException;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\Fixtures\ProviderFile;
use Lomkit\Osdd\Tests\TestCase;

class LayerResourcesTest extends TestCase
{
    public function test_views_use_the_package_name_as_default_namespace(): void
    {
        $layer = $this->createReceiptsLayer('functional');
        $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        $this->app['files']->put($layer.'/resources/views/hello.blade.php', 'Hello {{ $name }}');

        $this->bootProvider($layer, 'Functional\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views');")->register($this->app);

        $this->assertSame('Hello Ada', view('receipts::hello', ['name' => 'Ada'])->render());
    }

    public function test_views_accept_an_explicit_namespace(): void
    {
        $layer = $this->createReceiptsLayer('functional');
        $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        $this->app['files']->put($layer.'/resources/views/hello.blade.php', 'Hello');

        $this->bootProvider($layer, 'Functional\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views', 'billing-receipts');")->register($this->app);

        $this->assertSame('Hello', view('billing-receipts::hello')->render());
        $this->assertArrayNotHasKey('receipts', $this->app['view']->getFinder()->getHints());
    }

    public function test_a_missing_views_directory_registers_nothing(): void
    {
        $layer = $this->createReceiptsLayer('functional');

        $this->bootProvider($layer, 'Functional\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views');")->register($this->app);

        $this->assertArrayNotHasKey('receipts', $this->app['view']->getFinder()->getHints());
    }

    public function test_translations_load_namespaced_and_json_files(): void
    {
        $layer = $this->createReceiptsLayer('functional');
        $this->app['files']->ensureDirectoryExists($layer.'/lang/en');
        $this->app['files']->put($layer.'/lang/en/messages.php', "<?php\n\nreturn ['welcome' => 'Welcome to receipts'];\n");
        $this->app['files']->put($layer.'/lang/fr.json', '{"Download the receipt": "Télécharger le reçu"}');

        $this->bootProvider($layer, 'Functional\\Receipts', "\$this->withTranslations(__DIR__ . '/../../lang');")->register($this->app);

        $this->assertSame('Welcome to receipts', __('receipts::messages.welcome'));
        $this->assertSame('Télécharger le reçu', __('Download the receipt', locale: 'fr'));
    }

    public function test_class_components_render_under_the_package_prefix(): void
    {
        $layer = $this->createReceiptsLayer('functional');
        $componentNamespace = 'Functional\\Receipts\\View\\Components\\PackagePrefix';
        $this->writeComponent($layer, $componentNamespace, 'Badge', '<span class="badge">Paid</span>');

        $this->bootProvider(
            $layer,
            'Functional\\Receipts',
            "\$this->withComponents('".str_replace('\\', '\\\\', $componentNamespace)."');",
        )->register($this->app);

        $this->assertSame('<span class="badge">Paid</span>', trim(Blade::render('<x-receipts::badge />')));
    }

    public function test_components_accept_an_explicit_prefix(): void
    {
        $layer = $this->createReceiptsLayer('functional');
        $componentNamespace = 'Functional\\Receipts\\View\\Components\\ExplicitPrefix';
        $this->writeComponent($layer, $componentNamespace, 'Badge', '<span>Paid</span>');

        $this->bootProvider(
            $layer,
            'Functional\\Receipts',
            "\$this->withComponents('".str_replace('\\', '\\\\', $componentNamespace)."', 'billing');",
        )->register($this->app);

        $this->assertSame('<span>Paid</span>', trim(Blade::render('<x-billing::badge />')));
    }

    public function test_two_layers_with_the_same_package_name_collide_on_the_default_namespace(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();
        $first = $this->bootProvider($functional, 'Functional\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views');");
        $second = $this->bootProvider($technical, 'Technical\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views');");

        $first->register($this->app);

        try {
            $second->register($this->app);
            $this->fail('The second provider registered the same views namespace.');
        } catch (ResourceNamespaceCollisionException $exception) {
            $this->assertSame(
                "The views namespace [receipts] is registered by both [{$first->class}] and [{$second->class}]. Pass another namespace as the second argument of withViews() in one of them.",
                $exception->getMessage(),
            );
        }
    }

    public function test_an_explicit_namespace_resolves_the_collision(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();
        $this->app['files']->put($functional.'/resources/views/title.blade.php', 'Functional');
        $this->app['files']->put($technical.'/resources/views/title.blade.php', 'Technical');

        $this->bootProvider($functional, 'Functional\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views');")->register($this->app);
        $this->bootProvider($technical, 'Technical\\Receipts', "\$this->withViews(__DIR__ . '/../../resources/views', 'technical-receipts');")->register($this->app);

        $this->assertSame('Functional', view('receipts::title')->render());
        $this->assertSame('Technical', view('technical-receipts::title')->render());
    }

    public function test_component_prefixes_collide_like_view_namespaces(): void
    {
        [$functional, $technical] = $this->createTwoReceiptsLayers();

        $this->bootProvider($functional, 'Functional\\Receipts', "\$this->withComponents('Functional\\\\Receipts\\\\View\\\\Components');")->register($this->app);

        $this->expectException(ResourceNamespaceCollisionException::class);
        $this->expectExceptionMessage('The components prefix [receipts]');

        $this->bootProvider($technical, 'Technical\\Receipts', "\$this->withComponents('Technical\\\\Receipts\\\\View\\\\Components');")->register($this->app);
    }

    public function test_a_default_namespace_needs_a_discovered_layer_owning_the_provider(): void
    {
        $directory = $this->project()->path('outside-the-buckets/orphan');
        $provider = $this->bootProvider($directory, 'Functional\\Orphan', "\$this->withComponents('Functional\\\\Orphan\\\\View\\\\Components');");

        $this->expectException(ProviderLayerNotFoundException::class);
        $this->expectExceptionMessage("No discovered layer owns [{$provider->class}]");

        $provider->register($this->app);
    }

    /**
     * @return array{string, string}
     */
    private function createTwoReceiptsLayers(): array
    {
        $functional = $this->createReceiptsLayer('functional');
        $technical = $this->createReceiptsLayer('technical');
        $this->layerFixture()->bucket('technical');

        foreach ([$functional, $technical] as $layer) {
            $this->app['files']->ensureDirectoryExists($layer.'/resources/views');
        }

        return [$functional, $technical];
    }

    private function bootProvider(string $layer, string $namespace, string $boot): ProviderFile
    {
        return $this->layerFixture()->provider($layer, $namespace, LayerFixture::bootMethod($boot));
    }

    private function createReceiptsLayer(string $bucket): string
    {
        return $this->layerFixture()->layerAt($this->app->basePath($bucket.'/receipts'), $bucket.'/receipts', [
            'autoload' => ['psr-4' => [Str::studly($bucket).'\\Receipts\\' => 'src/']],
        ]);
    }

    private function writeComponent(string $layer, string $namespace, string $class, string $html): void
    {
        $path = $layer.'/src/'.str_replace('\\', '/', Str::after($namespace, 'Receipts\\')).'/'.$class.'.php';
        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, "<?php\n\nnamespace {$namespace};\n\nuse Illuminate\\View\\Component;\n\nclass {$class} extends Component\n{\n    public function render(): string\n    {\n        return '{$html}';\n    }\n}\n");

        require_once $path;
    }
}
