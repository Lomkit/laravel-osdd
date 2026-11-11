<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\Tests\TestCase;

class PhpunitCommandTest extends TestCase
{
    private string $xmlPath;

    private string $originalXml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit>
            <testsuites>
                <testsuite name="Unit">
                    <directory>tests</directory>
                </testsuite>
            </testsuites>
        </phpunit>
        XML;

    protected function setUp(): void
    {
        parent::setUp();

        $this->xmlPath = $this->app->basePath('phpunit.xml');
        $this->app['files']->put($this->xmlPath, $this->originalXml);

        $this->app['files']->makeDirectory(
            $this->app->basePath('functional/test-layer/tests/Feature'),
            0755,
            true,
            true
        );
    }

    protected function tearDown(): void
    {
        $this->app['files']->put($this->xmlPath, $this->originalXml);
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/tests'));

        parent::tearDown();
    }

    public function test_it_adds_testsuite_for_discovered_layers(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $suite = $xpath->query('//testsuites/testsuite[@name="functional/test-layer"]')->item(0);

        $this->assertNotNull($suite);
        $this->assertStringContainsString(
            'functional/test-layer/tests',
            $suite->getElementsByTagName('directory')->item(0)->nodeValue
        );
    }

    public function test_it_skips_already_present_testsuites(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xmlAfterFirst = $this->app['files']->get($this->xmlPath);

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame($xmlAfterFirst, $this->app['files']->get($this->xmlPath));
    }

    public function test_it_skips_layers_without_tests_directory(): void
    {
        $layerPath = $this->app->basePath('functional/no-tests-layer');
        $this->app['files']->makeDirectory($layerPath, 0755, true, true);
        $this->app['files']->put($layerPath.'/composer.json', json_encode([
            'name' => 'functional/no-tests-layer',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\NoTestsLayer\\' => 'src/']],
        ]));

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $suite = $xpath->query('//testsuites/testsuite[@name="functional/no-tests-layer"]')->item(0);

        $this->assertNull($suite);

        $this->app['files']->deleteDirectory($layerPath);
    }

    public function test_it_excludes_layer_migrations_from_coverage(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $directory = $xpath
            ->query('//source/exclude/directory[normalize-space()="functional/*/database/migrations"]')
            ->item(0);

        $this->assertNotNull($directory);
        $this->assertSame('.php', $directory->getAttribute('suffix'));
    }

    public function test_it_does_not_duplicate_the_migrations_exclusion(): void
    {
        $this->artisan('osdd:phpunit')->assertExitCode(0);
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $directories = $xpath->query('//source/exclude/directory[normalize-space()="functional/*/database/migrations"]');

        $this->assertSame(1, $directories->length);
    }

    public function test_it_syncs_the_coverage_source_of_laravels_default_phpunit_xml(): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->app['files']->put($this->xmlPath, <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit bootstrap="vendor/autoload.php" colors="true">
                <testsuites>
                    <testsuite name="Unit"><directory>tests/Unit</directory></testsuite>
                </testsuites>
                <source>
                    <include>
                        <directory>app</directory>
                    </include>
                </source>
            </phpunit>
            XML);

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xpath = $this->xpath();
        $this->assertSame(['app', 'functional/test-layer/src'], $this->texts($xpath, '//source/include/directory'));
        $this->assertSame(
            ['functional/*/database/migrations', 'technical/*/database/migrations'],
            $this->texts($xpath, '//source/exclude/directory'),
        );
        $this->assertSame('.php', $xpath->query('//source/include/directory')->item(1)->getAttribute('suffix'));
    }

    public function test_the_migrations_exclusion_matches_the_layer_migrations_only(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $excluded = glob($this->app->basePath($this->texts($this->xpath(), '//source/exclude/directory')[0]), GLOB_ONLYDIR);

        $this->assertSame([realpath($this->app->basePath('functional/test-layer/database/migrations'))], array_map('realpath', $excluded));
    }

    public function test_prune_removes_suites_of_removed_layers_and_missing_source_directories(): void
    {
        $this->app['files']->put($this->xmlPath, <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit>
                <testsuites>
                    <testsuite name="Unit"><directory>tests</directory></testsuite>
                    <testsuite name="functional/removed-layer"><directory>functional/removed-layer/tests</directory></testsuite>
                </testsuites>
                <source>
                    <include>
                        <directory>app</directory>
                        <directory>functional/removed-layer/src</directory>
                    </include>
                </source>
            </phpunit>
            XML);

        $this->artisan('osdd:phpunit')->assertExitCode(0);
        $this->assertContains('functional/removed-layer', $this->texts($this->xpath(), '//testsuites/testsuite/@name'));

        $this->artisan('osdd:phpunit', ['--prune' => true])->assertExitCode(0);

        $xpath = $this->xpath();
        $this->assertSame(['Unit', 'functional/test-layer'], $this->texts($xpath, '//testsuites/testsuite/@name'));
        $this->assertSame(['functional/test-layer/src'], $this->texts($xpath, '//source/include/directory'));
    }

    private function xpath(): \DOMXPath
    {
        $dom = new \DOMDocument;
        $dom->loadXML($this->app['files']->get($this->xmlPath));

        return new \DOMXPath($dom);
    }

    /**
     * @return list<string>
     */
    private function texts(\DOMXPath $xpath, string $expression): array
    {
        $texts = [];

        foreach ($xpath->query($expression) as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }

    public function test_it_fails_when_phpunit_xml_is_missing(): void
    {
        $this->app['files']->delete($this->xmlPath);

        $this->artisan('osdd:phpunit')->assertExitCode(1);
    }
}
