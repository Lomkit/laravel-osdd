<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Blade;
use Lomkit\Osdd\Tests\Concerns\CompilesViews;
use Lomkit\Osdd\Tests\Concerns\CreatesProviderLayer;
use Lomkit\Osdd\Tests\TestCase;

class ComponentMakeCommandTest extends TestCase
{
    use CompilesViews;
    use CreatesProviderLayer;

    private const string EMPTY_PROVIDER = <<<'PHP'
            public function boot(): void
            {
                //
            }
        PHP;

    protected function tearDown(): void
    {
        $this->deleteProviderLayer();
        $this->app['files']->deleteDirectory($this->app->basePath('technical'));
        $this->app['files']->delete($this->app->basePath('composer.json'));

        parent::tearDown();
    }

    public function test_it_writes_the_class_and_the_view_in_the_layer(): void
    {
        $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:component', ['name' => 'Alert', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $class = (string) file_get_contents($this->providerLayerPath('src/View/Components/Alert.php'));
        $this->assertStringContainsString('namespace Functional\ProviderLayer\View\Components;', $class);
        $this->assertStringContainsString("return view('provider-layer::components.alert');", $class);
        $this->assertFileExists($this->providerLayerPath('resources/views/components/alert.blade.php'));
        $this->assertFileDoesNotExist($this->app->basePath('resources/views/components/alert.blade.php'));
    }

    public function test_the_generated_component_renders_in_blade(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:component', ['name' => 'Banner', '--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('Registered')
            ->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertStringContainsString("\$this->withViews(__DIR__ . '/../../resources/views');", $source);
        $this->assertStringContainsString("\$this->withComponents('Functional\\\\ProviderLayer\\\\View\\\\Components');", $source);

        file_put_contents($this->providerLayerPath('resources/views/components/banner.blade.php'), '<div class="banner">{{ $slot }}</div>');
        require_once $this->providerLayerPath('src/View/Components/Banner.php');
        require_once $provider['path'];
        $this->app->register($provider['class']);

        $this->assertSame(
            '<div class="banner">Order saved</div>',
            trim(Blade::render('<x-provider-layer::banner>Order saved</x-provider-layer::banner>')),
        );
    }

    public function test_an_inline_component_has_no_view_and_registers_only_the_components(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:component', ['name' => 'Notice', '--layer' => self::PROVIDER_LAYER, '--inline' => true])->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertStringNotContainsString('withViews', $source);
        $this->assertStringContainsString('withComponents', $source);
        $this->assertDirectoryDoesNotExist($this->providerLayerPath('resources/views'));

        require_once $this->providerLayerPath('src/View/Components/Notice.php');
        require_once $provider['path'];
        $this->app->register($provider['class']);

        $this->assertStringContainsString('<div>', Blade::render('<x-provider-layer::notice />'));
    }

    public function test_an_anonymous_component_has_only_a_view(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);

        $this->artisan('osdd:component', ['name' => 'Ribbon', '--layer' => self::PROVIDER_LAYER, '--view' => true])->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertStringContainsString('withViews', $source);
        $this->assertStringNotContainsString('withComponents', $source);
        $this->assertFileDoesNotExist($this->providerLayerPath('src/View/Components/Ribbon.php'));

        $this->app['files']->put($this->app->basePath('composer.json'), '{"autoload": {"psr-4": {"App\\\\": "app/"}}}');

        file_put_contents($this->providerLayerPath('resources/views/components/ribbon.blade.php'), '<em>New</em>');
        require_once $provider['path'];
        $this->app->register($provider['class']);

        $this->assertSame('<em>New</em>', trim(Blade::render('<x-provider-layer::ribbon />')));
    }

    public function test_it_renders_with_the_namespace_the_booted_layer_already_uses(): void
    {
        $provider = $this->createProviderLayer(<<<'PHP'
                public function boot(): void
                {
                    $this->withViews(__DIR__ . '/../../resources/views', 'shop');
                }
            PHP);
        $this->app['files']->ensureDirectoryExists($this->providerLayerPath('resources/views'));
        require_once $provider['path'];
        $this->app->register($provider['class']);

        $this->artisan('osdd:component', ['name' => 'Price', '--layer' => self::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertStringContainsString(
            "return view('shop::components.price');",
            (string) file_get_contents($this->providerLayerPath('src/View/Components/Price.php')),
        );
        $this->assertSame(1, substr_count((string) file_get_contents($provider['path']), 'withViews('));
    }

    public function test_a_shared_package_name_gets_the_full_layer_name_as_namespace(): void
    {
        $provider = $this->createProviderLayer(self::EMPTY_PROVIDER);
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('technical/provider-layer'));
        $this->app['files']->put($this->app->basePath('technical/provider-layer/composer.json'), '{"name": "technical/provider-layer", "type": "layer"}');

        $this->artisan('osdd:component', ['name' => 'Tag', '--layer' => self::PROVIDER_LAYER])
            ->expectsOutputToContain('[functional-provider-layer]')
            ->assertExitCode(0);

        $source = (string) file_get_contents($provider['path']);
        $this->assertStringContainsString("\$this->withViews(__DIR__ . '/../../resources/views', 'functional-provider-layer');", $source);
        $this->assertStringContainsString("\$this->withComponents('Functional\\\\ProviderLayer\\\\View\\\\Components', 'functional-provider-layer');", $source);
        $this->assertStringContainsString(
            "return view('functional-provider-layer::components.tag');",
            (string) file_get_contents($this->providerLayerPath('src/View/Components/Tag.php')),
        );
    }

    private function providerLayerPath(string $path): string
    {
        return $this->app->basePath(self::PROVIDER_LAYER.'/'.$path);
    }
}
