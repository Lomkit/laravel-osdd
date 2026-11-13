<?php

namespace Lomkit\Osdd\Tests\Console\Providers;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\TestCase;

class LayerProviderTest extends TestCase
{
    use UsesTemporaryProject;

    private const string LAYER_SERVICE_PROVIDER = 'Lomkit\Osdd\LayerServiceProvider';

    private const string SERVICE_PROVIDER = 'Illuminate\Support\ServiceProvider';

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = $this->project()->path('layer-provider');
        (new Filesystem)->ensureDirectoryExists($this->directory.'/src/Providers');
    }

    public function test_it_finds_the_conventional_provider_of_a_dotted_package_name(): void
    {
        $this->writeProvider('FooBarServiceProvider', self::LAYER_SERVICE_PROVIDER);

        $provider = LayerProvider::of($this->layer([]));

        $this->assertSame($this->directory.'/src/Providers/FooBarServiceProvider.php', $provider?->path);
    }

    public function test_a_declared_provider_comes_before_the_conventional_one(): void
    {
        $this->writeProvider('FooBarServiceProvider', self::LAYER_SERVICE_PROVIDER);
        $this->writeProvider('BootServiceProvider', self::LAYER_SERVICE_PROVIDER);

        $provider = LayerProvider::of($this->layer(['Functional\FooBar\Providers\BootServiceProvider']));

        $this->assertSame($this->directory.'/src/Providers/BootServiceProvider.php', $provider?->path);
    }

    public function test_a_declared_provider_that_does_not_extend_layer_service_provider_is_skipped(): void
    {
        $this->writeProvider('HorizonServiceProvider', self::SERVICE_PROVIDER);
        $this->writeProvider('BootServiceProvider', self::LAYER_SERVICE_PROVIDER);

        $provider = LayerProvider::of($this->layer([
            'Functional\FooBar\Providers\HorizonServiceProvider',
            'Functional\FooBar\Providers\BootServiceProvider',
        ]));

        $this->assertSame($this->directory.'/src/Providers/BootServiceProvider.php', $provider?->path);
    }

    public function test_a_layer_whose_providers_do_not_extend_layer_service_provider_has_none(): void
    {
        $this->writeProvider('HorizonServiceProvider', self::SERVICE_PROVIDER);
        $this->writeProvider('FooBarServiceProvider', self::SERVICE_PROVIDER);

        $this->assertNull(LayerProvider::of($this->layer(['Functional\FooBar\Providers\HorizonServiceProvider'])));
    }

    public function test_a_layer_without_provider_file_has_none(): void
    {
        $this->assertNull(LayerProvider::of($this->layer(['Functional\FooBar\Providers\BootServiceProvider'])));
    }

    /**
     * @param  list<string>  $providers
     */
    private function layer(array $providers): Layer
    {
        return new Layer(LayerName::fromString('functional/foo.bar'), new LayerDirectory($this->directory), ['Functional\\FooBar\\' => 'src/'], [], $providers, [], []);
    }

    private function writeProvider(string $class, string $parent): void
    {
        file_put_contents(
            $this->directory."/src/Providers/{$class}.php",
            "<?php\n\nnamespace Functional\\FooBar\\Providers;\n\nuse {$parent};\n\nclass {$class} extends ".class_basename($parent)."\n{\n}\n",
        );
    }
}
