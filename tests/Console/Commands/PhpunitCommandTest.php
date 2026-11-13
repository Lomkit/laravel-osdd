<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function test_it_adds_testsuite_for_discovered_layers(): void
    {
        $this->artisan('osdd:phpunit')
            ->expectsOutputToContain('ADDED')
            ->expectsOutputToContain('phpunit.xml updated.')
            ->assertExitCode(0);

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

        $this->artisan('osdd:phpunit')
            ->expectsOutputToContain('already present')
            ->expectsOutputToContain('phpunit.xml is already up to date.')
            ->assertExitCode(0);

        $this->assertSame($xmlAfterFirst, $this->app['files']->get($this->xmlPath));
    }

    public function test_it_skips_layers_without_tests_directory(): void
    {
        $this->layerFixture()->layer('functional/no-tests-layer', ['autoload' => ['psr-4' => ['Functional\\NoTestsLayer\\' => 'src/']]]);

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xml = $this->app['files']->get($this->xmlPath);
        $dom = new \DOMDocument;
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $suite = $xpath->query('//testsuites/testsuite[@name="functional/no-tests-layer"]')->item(0);

        $this->assertNull($suite);
    }

    public function test_it_writes_one_source_include_per_layer_and_no_exclusion(): void
    {
        $this->layerFixture()->bucket('technical');
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $xpath = $this->xpath();
        $this->assertSame(['functional/test-layer/src'], $this->texts($xpath, '//source/include/directory'));
        $this->assertSame(0, $xpath->query('//source/exclude')->length);
    }

    public function test_it_syncs_the_coverage_source_of_laravels_default_phpunit_xml(): void
    {
        $this->layerFixture()->bucket('technical');
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
        $this->assertSame(0, $xpath->query('//source/exclude')->length);
        $this->assertSame('.php', $xpath->query('//source/include/directory')->item(1)->getAttribute('suffix'));
    }

    public function test_prune_removes_suites_of_removed_layers_and_missing_source_directories(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function coveringIncludes(): iterable
    {
        yield 'glob' => ['<directory>functional/*/src</directory>'];
        yield 'globstar' => ['<directory>functional/**/src</directory>'];
        yield 'dot slash and trailing slash' => ['<directory suffix=".php"> ./functional/test-layer/src/ </directory>'];
        yield 'parent folder' => ['<directory>functional</directory>'];
        yield 'empty prefix and suffix' => ['<directory prefix="" suffix="">functional/test-layer</directory>'];
    }

    #[DataProvider('coveringIncludes')]
    public function test_a_layer_source_already_covered_by_an_include_is_not_added(string $include): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->writeXmlWithSource("<include>{$include}</include>");

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertCount(1, $this->xpath()->query('//source/include/directory'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonCoveringIncludes(): iterable
    {
        yield 'prefix' => ['<directory prefix="Order">functional/*/src</directory>'];
        yield 'other suffix' => ['<directory suffix=".inc">functional/*/src</directory>'];
        yield 'sibling folder' => ['<directory>functional/test-layer/tests</directory>'];
        yield 'folder sharing the name start' => ['<directory>functional/test</directory>'];
    }

    #[DataProvider('nonCoveringIncludes')]
    public function test_a_layer_source_not_covered_by_an_include_is_added(string $include): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test'));
        $this->writeXmlWithSource("<include>{$include}</include>");

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertContains('functional/test-layer/src', $this->texts($this->xpath(), '//source/include/directory'));
    }

    public function test_the_exclusions_already_in_the_file_are_kept(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->writeXmlWithSource(<<<'XML'
            <include>
                <directory>functional</directory>
            </include>
            <exclude>
                <directory>functional/*/database/migrations</directory>
                <directory>technical/*/database/migrations</directory>
            </exclude>
            XML);

        $this->artisan('osdd:phpunit', ['--prune' => true])->assertExitCode(0);

        $this->assertSame(
            ['functional/*/database/migrations', 'technical/*/database/migrations'],
            $this->texts($this->xpath(), '//source/exclude/directory'),
        );
    }

    public function test_prune_keeps_glob_entries_and_removes_missing_literal_ones(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->writeXmlWithSource(<<<'XML'
            <include>
                <directory>functional/*/src</directory>
                <directory>modules/**/src</directory>
                <directory>legacy/[ab]</directory>
                <directory>./functional/test-layer/src/</directory>
                <directory>functional/removed-layer/src</directory>
            </include>
            XML);

        $this->artisan('osdd:phpunit', ['--prune' => true])
            ->expectsOutputToContain('functional/removed-layer/src')
            ->assertExitCode(0);

        $this->assertSame(
            ['functional/*/src', 'modules/**/src', 'legacy/[ab]', './functional/test-layer/src/'],
            $this->texts($this->xpath(), '//source/include/directory'),
        );
    }

    private function writeXmlWithSource(string $source): void
    {
        $this->app['files']->put($this->xmlPath, <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit>
                <testsuites>
                    <testsuite name="Unit"><directory>tests</directory></testsuite>
                </testsuites>
                <source>
                    {$source}
                </source>
            </phpunit>
            XML);
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

        $this->artisan('osdd:phpunit')
            ->expectsOutputToContain('phpunit.xml not found')
            ->assertExitCode(1);
    }
}
