<?php

namespace Lomkit\Osdd\Tests\Livewire;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;
use Livewire\LivewireManager;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use PHPUnit\Framework\Attributes\RequiresMethod;

#[RequiresMethod(LivewireManager::class, 'addNamespace')]
class LivewireMakeCommandTest extends LivewireTestCase
{
    public function test_it_is_registered_with_the_layer_option_and_the_livewire_options(): void
    {
        $definition = $this->app->make(Kernel::class)->all()['osdd:livewire']->getDefinition();

        foreach (['layer', 'class', 'sfc', 'mfc', 'test', 'emoji'] as $option) {
            $this->assertTrue($definition->hasOption($option), "osdd:livewire has no --{$option} option.");
        }
    }

    public function test_a_class_component_is_generated_in_the_layer_and_renders_its_layer_view(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'Cart', '--class' => true, '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Registered')
            ->assertExitCode(0);
        $cart = GeneratedClass::load($this->layerPath('src/Livewire/Cart.php'), 'Functional\ProviderLayer\Livewire\Cart');
        $this->write('resources/views/livewire/cart.blade.php', '<div>Cart is empty</div>');
        $provider->register($this->app);

        $this->assertSame('provider-layer::livewire.cart', $cart->newInstance()->render()->name());
        $this->assertStringContainsString('Cart is empty', Blade::render('<livewire:provider-layer::cart />'));
        $this->assertSame(1, substr_count($provider->source(), '$this->withLivewire();'));
        $this->assertSame(1, substr_count($provider->source(), 'withViews('));
        $this->assertFileDoesNotExist($this->project()->path('app/Livewire/Cart.php'));
    }

    public function test_a_single_file_component_is_generated_in_the_layer_views(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'Search', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $provider->register($this->app);

        $this->assertFileExists($this->layerPath('resources/views/livewire/⚡search.blade.php'));
        $this->assertStringContainsString('wire:id', Blade::render('<livewire:provider-layer::search />'));
        $this->assertStringContainsString('$this->withLivewire();', $provider->source());
        $this->assertStringNotContainsString('withViews(', $provider->source());
    }

    public function test_a_nested_class_component_keeps_its_folders(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'Admin/OrderList', '--class' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $list = GeneratedClass::load($this->layerPath('src/Livewire/Admin/OrderList.php'), 'Functional\ProviderLayer\Livewire\Admin\OrderList');
        $provider->register($this->app);

        $this->assertSame('provider-layer::livewire.admin.order-list', $list->newInstance()->render()->name());
        $this->assertFileExists($this->layerPath('resources/views/livewire/admin/order-list.blade.php'));
    }

    public function test_a_second_component_does_not_register_the_namespace_again(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'Wishlist', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:livewire', ['name' => 'Basket', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertSame(1, substr_count($provider->source(), 'withLivewire('));
    }

    public function test_the_test_of_a_class_component_is_written_in_the_layer(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'Checkout', '--class' => true, '--test' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertStringContainsString("Livewire::test('provider-layer::checkout')", (string) file_get_contents($this->layerPath('tests/Feature/Livewire/CheckoutTest.php')));
        $this->assertDirectoryDoesNotExist($this->project()->path('tests'));
    }

    public function test_a_namespace_given_in_the_name_is_replaced_by_the_layer_one(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());

        $this->artisan('osdd:livewire', ['name' => 'billing::invoice', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);

        $this->assertFileExists($this->layerPath('resources/views/livewire/⚡invoice.blade.php'));
    }

    public function test_a_shared_package_name_gets_the_full_layer_name_as_namespace(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->layerFixture()->bucket('technical');
        $this->layerFixture()->layer('technical/provider-layer');

        $this->artisan('osdd:livewire', ['name' => 'Coupon', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('[functional-provider-layer]')
            ->assertExitCode(0);
        $provider->register($this->app);

        $this->assertStringContainsString("\$this->withLivewire('functional-provider-layer');", $provider->source());
        $this->assertStringContainsString('wire:id', Blade::render('<livewire:functional-provider-layer::coupon />'));
    }

    public function test_an_existing_component_is_not_overwritten(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->write('resources/views/livewire/⚡filters.blade.php', '<div>Mine</div>');

        $this->artisan('osdd:livewire', ['name' => 'Filters', '--sfc' => true, '--layer' => LayerFixture::PROVIDER_LAYER])
            ->expectsOutputToContain('Component already exists.')
            ->assertExitCode(1);

        $this->assertSame('<div>Mine</div>', file_get_contents($this->layerPath('resources/views/livewire/⚡filters.blade.php')));
    }
}
