<?php

namespace Lomkit\Osdd\Tests\Livewire;

use Illuminate\Support\Facades\Blade;
use Livewire\LivewireManager;
use Lomkit\Osdd\Resources\ResourceNamespaceCollisionException;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use PHPUnit\Framework\Attributes\RequiresMethod;

#[RequiresMethod(LivewireManager::class, 'addNamespace')]
class LayerLivewireTest extends LivewireTestCase
{
    public function test_a_class_component_of_the_layer_renders_under_the_layer_namespace(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withViews(__DIR__ . '/../../resources/views');\n        \$this->withLivewire();"));
        $this->writeCounter('Counter', 'provider-layer::livewire.counter');
        $provider->register($this->app);

        $this->assertStringContainsString('Count: 3', Blade::render('<livewire:provider-layer::counter />'));
    }

    public function test_a_single_file_component_of_the_layer_renders_under_the_layer_namespace(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod('$this->withLivewire();'));
        $this->write('resources/views/livewire/⚡greeting.blade.php', "<?php\n\nuse Livewire\\Component;\n\nnew class extends Component\n{\n    public string \$name = 'Ada';\n};\n?>\n\n<div>Hello {{ \$name }}</div>\n");
        $provider->register($this->app);

        $this->assertStringContainsString('Hello Ada', Blade::render('<livewire:provider-layer::greeting />'));
    }

    public function test_an_explicit_namespace_replaces_the_package_name(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withViews(__DIR__ . '/../../resources/views');\n        \$this->withLivewire('shop');"));
        $this->writeCounter('Tally', 'provider-layer::livewire.tally');
        $provider->register($this->app);

        $this->assertStringContainsString('Count: 3', Blade::render('<livewire:shop::tally />'));
    }

    public function test_two_providers_cannot_claim_the_same_livewire_namespace(): void
    {
        $first = $this->layerFixture()->providerLayer(LayerFixture::bootMethod("\$this->withLivewire('shop');"));
        $second = $this->layerFixture()->provider($this->layerPath(''), 'Functional\\ProviderLayer', LayerFixture::bootMethod("\$this->withLivewire('shop');"));
        $first->register($this->app);

        $this->expectException(ResourceNamespaceCollisionException::class);
        $this->expectExceptionMessage('withLivewire()');

        $second->register($this->app);
    }

    private function writeCounter(string $class, string $view): void
    {
        $this->write("src/Livewire/{$class}.php", "<?php\n\nnamespace Functional\\ProviderLayer\\Livewire;\n\nuse Livewire\\Component;\n\nclass {$class} extends Component\n{\n    public int \$count = 3;\n\n    public function render()\n    {\n        return view('{$view}');\n    }\n}\n");
        $this->write('resources/views/livewire/'.strtolower($class).'.blade.php', '<div>Count: {{ $count }}</div>');
        GeneratedClass::load($this->layerPath("src/Livewire/{$class}.php"), "Functional\\ProviderLayer\\Livewire\\{$class}");
    }
}
