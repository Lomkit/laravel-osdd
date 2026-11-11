<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RootComposerJsonTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().'/osdd-root-composer-'.uniqid().'/project';
        mkdir($this->basePath, 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory(dirname($this->basePath));

        parent::tearDown();
    }

    public function test_it_tells_whether_the_project_has_a_composer_json(): void
    {
        $root = new RootComposerJson(new Filesystem, $this->basePath);
        $this->assertFalse($root->exists());

        file_put_contents($this->basePath.'/composer.json', '{}');
        $this->assertTrue($root->exists());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function layerDirectories(): array
    {
        return [
            'bucket in the project' => ['/functional/orders', './functional/*'],
            'nested bucket' => ['/layers/functional/orders', './layers/functional/*'],
            'layer at the project root' => ['/orders', './*'],
            'bucket next to the project' => ['/../functional/orders', '../functional/*'],
        ];
    }

    #[DataProvider('layerDirectories')]
    public function test_it_requires_the_layer_through_a_wildcard_path_repository(string $layerDirectory, string $url): void
    {
        file_put_contents($this->basePath.'/composer.json', "{\n    \"require\": {}\n}\n");

        (new RootComposerJson(new Filesystem, $this->basePath))
            ->requireLayer(LayerName::fromString('functional/orders'), new LayerDirectory($this->basePath.$layerDirectory));

        $composer = json_decode((string) file_get_contents($this->basePath.'/composer.json'), true);
        $this->assertSame(['functional/orders' => '*'], $composer['require']);
        $this->assertSame([['type' => 'path', 'url' => $url]], $composer['repositories']);
    }
}
