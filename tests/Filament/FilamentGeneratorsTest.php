<?php

namespace Lomkit\Osdd\Tests\Filament;

use Filament\Panel;
use Filament\PanelRegistry;
use Lomkit\Osdd\Filament\LayersPlugin;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use PHPUnit\Framework\Attributes\RequiresMethod;

#[RequiresMethod(Panel::class, 'discoverResources')]
class FilamentGeneratorsTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['files']->put($this->project()->path('composer.json'), '{"autoload": {"psr-4": {"App\\\\": "app/"}}}');
    }

    public function test_make_filament_resource_writes_the_resource_in_the_layer_given_as_resource_namespace(): void
    {
        $this->layerFixture()->providerLayer(LayerFixture::bootMethod());
        $this->layerFixture()->layer('functional/catalog', ['autoload' => ['psr-4' => ['Functional\\Catalog\\' => 'src/']]]);
        $this->registerAdminPanel();

        $this->artisan('make:filament-resource', [
            'model' => 'Invoice',
            '--panel' => 'admin',
            '--model-namespace' => 'Functional\ProviderLayer\Models',
            '--resource-namespace' => 'Functional\ProviderLayer\Filament\Admin\Resources',
            '--record-title-attribute' => 'reference',
        ])
            ->expectsConfirmation('Would you like to generate a read-only view page for the resource?', 'no')
            ->expectsConfirmation('Should the configuration be generated from the current database columns?', 'no')
            ->expectsConfirmation('Does the model use soft-deletes?', 'no')
            ->assertExitCode(0);

        $resource = (string) file_get_contents($this->layerPath('src/Filament/Admin/Resources/Invoices/InvoiceResource.php'));
        $this->assertStringContainsString('namespace Functional\ProviderLayer\Filament\Admin\Resources\Invoices;', $resource);
        $this->assertStringContainsString('use Functional\ProviderLayer\Models\Invoice;', $resource);
        $this->assertDirectoryDoesNotExist($this->project()->path('app/Filament'));
        $this->assertDirectoryDoesNotExist($this->project()->path('functional/catalog/src/Filament'));
    }

    public function test_make_filament_page_writes_the_page_and_its_view_in_the_layer(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withViews(__DIR__ . '/../../resources/views');"));
        $this->app['files']->ensureDirectoryExists($this->layerPath('resources/views'));
        $provider->register($this->app);
        $this->registerAdminPanel();

        $this->artisan('make:filament-page', ['name' => 'Reports', '--panel' => 'admin'])
            ->expectsConfirmation('Would you like to create this page in a resource?', 'no')
            ->assertExitCode(0);

        $page = (string) file_get_contents($this->layerPath('src/Filament/Admin/Pages/Reports.php'));
        $this->assertStringContainsString('namespace Functional\ProviderLayer\Filament\Admin\Pages;', $page);
        $this->assertStringContainsString("'provider-layer::filament.admin.pages.reports'", $page);
        $this->assertFileExists($this->layerPath('resources/views/filament/admin/pages/reports.blade.php'));
        $this->assertDirectoryDoesNotExist($this->project()->path('resources/views/filament'));
    }

    private function registerAdminPanel(): void
    {
        $this->app->make(PanelRegistry::class)->register(Panel::make()->id('admin')->default()->plugin(LayersPlugin::make()));
    }
}
