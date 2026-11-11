<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\ComposerJsonFile;
use Lomkit\Osdd\Composer\InvalidComposerJsonException;
use PHPUnit\Framework\TestCase;

class ComposerJsonFileTest extends TestCase
{
    private string $directory;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/osdd-composer-json-'.uniqid();
        mkdir($this->directory, 0755, true);
        $this->path = $this->directory.'/composer.json';
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);

        parent::tearDown();
    }

    public function test_it_keeps_empty_objects_as_objects(): void
    {
        file_put_contents($this->path, <<<'JSON'
            {
                "require": {},
                "require-dev": {},
                "autoload": {"psr-4": {"Database\\Seeders\\": "database/seeders/"}},
                "config": {"allow-plugins": {}},
                "keywords": []
            }
            JSON);

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->removeAutoloadNamespace('Database\\Seeders\\');
        $composer->save();

        $decoded = json_decode((string) file_get_contents($this->path), false);

        $this->assertEquals(new \stdClass, $decoded->{'require-dev'});
        $this->assertEquals(new \stdClass, $decoded->config->{'allow-plugins'});
        $this->assertEquals(new \stdClass, $decoded->autoload->{'psr-4'});
        $this->assertSame([], $decoded->keywords);
        $this->assertStringContainsString('"require-dev": {}', (string) file_get_contents($this->path));
    }

    public function test_it_writes_with_composer_encoding(): void
    {
        file_put_contents($this->path, json_encode(['description' => 'Gestion des données', 'require' => new \stdClass]));

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->save();

        $this->assertSame(<<<'JSON'
            {
                "description": "Gestion des données",
                "require": {
                    "functional/orders": "*"
                }
            }

            JSON, file_get_contents($this->path));
    }

    public function test_it_keeps_the_indentation_of_the_file(): void
    {
        file_put_contents($this->path, "{\n  \"require\": {\n    \"php\": \"^8.3\"\n  }\n}\n");

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->save();

        $this->assertSame("{\n  \"require\": {\n    \"php\": \"^8.3\",\n    \"functional/orders\": \"*\"\n  }\n}\n", file_get_contents($this->path));
    }

    public function test_it_appends_the_requirement_when_packages_are_not_sorted(): void
    {
        file_put_contents($this->path, json_encode(['require' => ['php' => '^8.3', 'laravel/framework' => '^13.0']]));

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->save();

        $this->assertSame(['php', 'laravel/framework', 'functional/orders'], array_keys($this->decoded()['require']));
    }

    public function test_it_sorts_the_requirements_like_composer_when_sort_packages_is_on(): void
    {
        file_put_contents($this->path, json_encode([
            'require' => ['php' => '^8.3', 'ext-json' => '*', 'laravel/framework' => '^13.0', 'technical/osdd' => '*'],
            'config' => ['sort-packages' => true],
        ]));

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->save();

        $this->assertSame(
            ['php', 'ext-json', 'functional/orders', 'laravel/framework', 'technical/osdd'],
            array_keys($this->decoded()['require']),
        );
    }

    public function test_it_keeps_an_existing_requirement(): void
    {
        file_put_contents($this->path, json_encode(['require' => ['functional/orders' => '^1.0']]));

        $composer = $this->open();
        $composer->requirePackage('functional/orders', '*');
        $composer->save();

        $this->assertSame(['functional/orders' => '^1.0'], $this->decoded()['require']);
    }

    public function test_it_dedupes_path_repositories_by_normalised_url(): void
    {
        file_put_contents($this->path, json_encode(['repositories' => [
            ['type' => 'path', 'url' => 'functional/*'],
            ['type' => 'vcs', 'url' => 'https://example.com/repo.git'],
        ]]));

        $composer = $this->open();
        $composer->addPathRepository('./functional/*');
        $composer->addPathRepository($this->directory.'/functional/*');
        $composer->addPathRepository('./technical/*');
        $composer->save();

        $this->assertSame(
            ['functional/*', 'https://example.com/repo.git', './technical/*'],
            array_column($this->decoded()['repositories'], 'url'),
        );
    }

    public function test_it_adds_a_path_repository_to_keyed_repositories(): void
    {
        file_put_contents($this->path, json_encode(['repositories' => ['private' => ['type' => 'composer', 'url' => 'https://repo.example.com']]]));

        $composer = $this->open();
        $composer->addPathRepository('./functional/*');
        $composer->save();

        $this->assertSame(['type' => 'path', 'url' => './functional/*'], $this->decoded()['repositories']['./functional/*']);
        $this->assertArrayHasKey('private', $this->decoded()['repositories']);
    }

    public function test_it_adds_a_laravel_provider_once(): void
    {
        file_put_contents($this->path, json_encode(['name' => 'functional/orders', 'extra' => []]));

        $composer = $this->open();
        $composer->addLaravelProvider('Functional\\Orders\\Providers\\OrdersServiceProvider');
        $composer->addLaravelProvider('Functional\\Orders\\Providers\\OrdersServiceProvider');
        $composer->save();

        $this->assertSame(
            ['Functional\\Orders\\Providers\\OrdersServiceProvider'],
            $this->decoded()['extra']['laravel']['providers'],
        );
        $this->assertSame(['Functional\\Orders\\Providers\\OrdersServiceProvider'], $this->open()->laravelProviders());
    }

    public function test_it_refuses_a_section_that_is_not_an_object(): void
    {
        file_put_contents($this->path, json_encode(['require' => 'laravel/framework']));

        $this->expectException(InvalidComposerJsonException::class);
        $this->expectExceptionMessage('"require" must be a JSON object');

        $this->open()->requirePackage('functional/orders', '*');
    }

    private function open(): ComposerJsonFile
    {
        return ComposerJsonFile::open(new Filesystem, $this->path);
    }

    /**
     * @return array<string, mixed>
     */
    private function decoded(): array
    {
        return json_decode((string) file_get_contents($this->path), true);
    }
}
