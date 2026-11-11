<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Support\Facades\Blade;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

class ComponentMakeCommandTest extends TestCase
{
    public function test_the_component_renders_its_layer_view(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:component', ['name' => 'Banner', '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Registered')
            ->assertExitCode(0);
        file_put_contents($this->layerPath('resources/views/components/banner.blade.php'), '<div class="banner">{{ $slot }}</div>');
        GeneratedClass::load($this->layerPath('src/View/Components/Banner.php'), 'Functional\ProviderLayer\View\Components\Banner');
        $provider->register($this->app);

        $this->assertSame(
            '<div class="banner">Order saved</div>',
            trim(Blade::render('<x-provider-layer::banner>Order saved</x-provider-layer::banner>')),
        );
        $this->assertFileDoesNotExist($this->project()->path('resources/views/components/banner.blade.php'));
    }

    public function test_an_inline_component_has_no_view_and_registers_only_the_components(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:component', ['name' => 'Notice', '--layer' => LayerFixture::PROVIDER_LAYER, '--inline' => true])->assertExitCode(0);
        GeneratedClass::load($this->layerPath('src/View/Components/Notice.php'), 'Functional\ProviderLayer\View\Components\Notice');
        $provider->register($this->app);

        $this->assertDirectoryDoesNotExist($this->layerPath('resources/views'));
        $this->assertArrayNotHasKey('provider-layer', $this->app['view']->getFinder()->getHints());
        $this->assertStringContainsString('<div>', Blade::render('<x-provider-layer::notice />'));
    }

    public function test_an_anonymous_component_has_only_a_view(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:component', ['name' => 'Ribbon', '--layer' => LayerFixture::PROVIDER_LAYER, '--view' => true])->assertExitCode(0);
        $this->app['files']->put($this->project()->path('composer.json'), '{"autoload": {"psr-4": {"App\\\\": "app/"}}}');
        file_put_contents($this->layerPath('resources/views/components/ribbon.blade.php'), '<em>New</em>');
        $provider->register($this->app);

        $this->assertFileDoesNotExist($this->layerPath('src/View/Components/Ribbon.php'));
        $this->assertStringNotContainsString('withComponents', $provider->source());
        $this->assertSame('<em>New</em>', trim(Blade::render('<x-provider-layer::ribbon />')));
    }

    public function test_it_renders_with_the_namespace_the_booted_layer_already_uses(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withViews(__DIR__ . '/../../resources/views', 'shop');"));
        $this->app['files']->ensureDirectoryExists($this->layerPath('resources/views'));
        $provider->register($this->app);

        $this->artisan('osdd:component', ['name' => 'Price', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $price = GeneratedClass::load($this->layerPath('src/View/Components/Price.php'), 'Functional\ProviderLayer\View\Components\Price');

        $this->assertSame('shop::components.price', $price->newInstance()->render()->name());
        $this->assertSame(1, substr_count($provider->source(), 'withViews('));
    }

    public function test_a_shared_package_name_gets_the_full_layer_name_as_namespace(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->layerFixture()->bucket('technical');
        $this->layerFixture()->layer('technical/provider-layer');

        $this->artisan('osdd:component', ['name' => 'Tag', '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('[functional-provider-layer]')
            ->assertExitCode(0);
        file_put_contents($this->layerPath('resources/views/components/tag.blade.php'), '<b>{{ $slot }}</b>');
        GeneratedClass::load($this->layerPath('src/View/Components/Tag.php'), 'Functional\ProviderLayer\View\Components\Tag');
        $provider->register($this->app);

        $this->assertSame('<b>Sale</b>', trim(Blade::render('<x-functional-provider-layer::tag>Sale</x-functional-provider-layer::tag>')));
    }

    private function layerPath(string $path): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/'.$path);
    }
}
