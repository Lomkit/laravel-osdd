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

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = $this->project()->path('layer-provider');
        (new Filesystem)->ensureDirectoryExists($this->directory.'/src/Providers');
    }

    public function test_it_finds_the_conventional_provider_of_a_dotted_package_name(): void
    {
        touch($this->directory.'/src/Providers/FooBarServiceProvider.php');

        $provider = LayerProvider::of($this->layer([]));

        $this->assertTrue($provider->exists());
        $this->assertSame($this->directory.'/src/Providers/FooBarServiceProvider.php', $provider->path);
    }

    public function test_a_declared_provider_comes_before_the_conventional_one(): void
    {
        touch($this->directory.'/src/Providers/FooBarServiceProvider.php');
        touch($this->directory.'/src/Providers/BootServiceProvider.php');

        $provider = LayerProvider::of($this->layer(['Functional\FooBar\Providers\BootServiceProvider']));

        $this->assertSame($this->directory.'/src/Providers/BootServiceProvider.php', $provider->path);
    }

    /**
     * @param  list<string>  $providers
     */
    private function layer(array $providers): Layer
    {
        return new Layer(LayerName::fromString('functional/foo.bar'), new LayerDirectory($this->directory), ['Functional\\FooBar\\' => 'src/'], [], $providers, [], []);
    }
}
