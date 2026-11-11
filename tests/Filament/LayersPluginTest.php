<?php

namespace Lomkit\Osdd\Tests\Filament;

use Filament\Panel;
use Lomkit\Osdd\Filament\LayersPlugin;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use PHPUnit\Framework\Attributes\RequiresMethod;

#[RequiresMethod(Panel::class, 'discoverResources')]
class LayersPluginTest extends FilamentTestCase
{
    private const string ADMIN = 'Functional\ProviderLayer\Filament\Admin';

    public function test_the_panel_discovers_the_resources_pages_widgets_and_clusters_of_each_layer(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->writeClass('src/Filament/Admin/Resources/Orders/OrderResource.php', self::ADMIN.'\Resources\Orders', 'OrderResource', 'class OrderResource extends \Filament\Resources\Resource {}');
        $this->writeClass('src/Filament/Admin/Pages/Settings.php', self::ADMIN.'\Pages', 'Settings', 'class Settings extends \Filament\Pages\Page {}');
        $this->writeClass('src/Filament/Admin/Widgets/SalesChart.php', self::ADMIN.'\Widgets', 'SalesChart', 'class SalesChart extends \Filament\Widgets\Widget {}');
        $this->writeClass('src/Filament/Admin/Clusters/Billing.php', self::ADMIN.'\Clusters', 'Billing', 'class Billing extends \Filament\Clusters\Cluster {}');

        $panel = Panel::make()->id('admin')->plugin(LayersPlugin::make());

        $this->assertContains(self::ADMIN.'\Resources\Orders\OrderResource', $panel->getResources());
        $this->assertContains(self::ADMIN.'\Pages\Settings', $panel->getPages());
        $this->assertContains(self::ADMIN.'\Widgets\SalesChart', $panel->getWidgets());
        $this->assertContains(self::ADMIN.'\Clusters\Billing', $panel->getClusters());
    }

    public function test_a_panel_only_discovers_the_folder_named_after_its_id(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->writeClass('src/Filament/Shop/Resources/ProductResource.php', 'Functional\ProviderLayer\Filament\Shop\Resources', 'ProductResource', 'class ProductResource extends \Filament\Resources\Resource {}');

        $admin = Panel::make()->id('admin')->plugin(LayersPlugin::make());
        $shop = Panel::make()->id('shop')->plugin(LayersPlugin::make());

        $this->assertSame([], $admin->getResources());
        $this->assertSame(['Functional\ProviderLayer\Filament\Shop\Resources\ProductResource'], array_values($shop->getResources()));
    }

    public function test_every_layer_with_a_namespace_is_offered_as_a_location_for_the_panel(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->layerFixture()->layer('functional/without-namespace');

        $panel = Panel::make()->id('back-office')->plugin(LayersPlugin::make());

        $this->assertSame([realpath($this->layerPath('')).'/src/Filament/BackOffice/Resources'], $panel->getResourceDirectories());
        $this->assertSame(['Functional\ProviderLayer\Filament\BackOffice\Resources'], $panel->getResourceNamespaces());
        $this->assertSame(['Functional\ProviderLayer\Filament\BackOffice\Pages'], $panel->getPageNamespaces());
        $this->assertSame(['Functional\ProviderLayer\Filament\BackOffice\Widgets'], $panel->getWidgetNamespaces());
        $this->assertSame(['Functional\ProviderLayer\Filament\BackOffice\Clusters'], $panel->getClusterNamespaces());
    }
}
