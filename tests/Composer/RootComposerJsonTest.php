<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\MalformedComposerJsonException;
use Lomkit\Osdd\Composer\RootComposerJson;
use Lomkit\Osdd\Layers\LayerDirectory;
use Lomkit\Osdd\Layers\LayerName;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RootComposerJsonTest extends TestCase
{
    use UsesTemporaryProject;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = $this->project()->path('root-composer/project');
        mkdir($this->basePath, 0755, true);
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

    public function test_it_reads_nothing_from_a_project_without_composer_json(): void
    {
        $root = new RootComposerJson(new Filesystem, $this->basePath);

        $this->assertSame([], $root->psr4());
        $this->assertSame([], $root->requiredPackages());
        $this->assertSame([], $root->pathRepositories());
    }

    public function test_it_reads_the_autoload_the_requirements_and_the_path_repositories(): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode([
            'require' => ['php' => '^8.3', 'functional/orders' => '*'],
            'require-dev' => ['phpunit/phpunit' => '^12.0'],
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
            'repositories' => [['type' => 'path', 'url' => './functional/*']],
        ]));
        mkdir($this->basePath.'/functional/orders', 0755, true);

        $root = new RootComposerJson(new Filesystem, $this->basePath);

        $this->assertSame(['App\\' => 'app/'], $root->psr4());
        $this->assertSame(['php', 'functional/orders', 'phpunit/phpunit'], $root->requiredPackages());
        $this->assertCount(1, $root->pathRepositories());
        $this->assertTrue($root->pathRepositories()[0]->covers($this->basePath.'/functional/orders'));
    }

    public function test_it_removes_autoload_namespaces_and_keeps_the_layout(): void
    {
        $json = "{\n  \"autoload\": {\n    \"psr-4\": {\n      \"App\\\\\": \"app/\",\n      \"Database\\\\Seeders\\\\\": \"database/seeders/\"\n    }\n  },\n  \"autoload-dev\": {\n    \"psr-4\": {\n      \"Database\\\\Seeders\\\\\": \"database/seeders/\"\n    }\n  }\n}\n";
        file_put_contents($this->basePath.'/composer.json', $json);

        (new RootComposerJson(new Filesystem, $this->basePath))->removeAutoloadNamespaces('Database\\Factories\\', 'Database\\Seeders\\');

        $this->assertSame(
            "{\n  \"autoload\": {\n    \"psr-4\": {\n      \"App\\\\\": \"app/\"\n    }\n  },\n  \"autoload-dev\": {\n    \"psr-4\": {}\n  }\n}\n",
            file_get_contents($this->basePath.'/composer.json'),
        );
    }

    public function test_a_broken_composer_json_is_reported_with_its_path(): void
    {
        file_put_contents($this->basePath.'/composer.json', '"text"');

        $this->expectException(MalformedComposerJsonException::class);
        $this->expectExceptionMessage('Cannot read ['.$this->basePath.'/composer.json]: it is not a JSON object.');

        (new RootComposerJson(new Filesystem, $this->basePath))->requiredPackages();
    }
}
