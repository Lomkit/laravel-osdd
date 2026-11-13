<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\Tests\TestCase;

class PhpunitCommandFormattingTest extends TestCase
{
    private const string LARAVEL_PHPUNIT_XML = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
                 bootstrap="vendor/autoload.php"
                 colors="true"
        >
            <testsuites>
                <testsuite name="Unit">
                    <directory>tests/Unit</directory>
                </testsuite>
                <testsuite name="Feature">
                    <directory>tests/Feature</directory>
                </testsuite>
            </testsuites>
            <source>
                <include>
                    <directory>app</directory>
                </include>
            </source>
            <php>
                <env name="APP_ENV" value="testing"/>
                <env name="APP_MAINTENANCE_DRIVER" value="file"/>
                <env name="BCRYPT_ROUNDS" value="4"/>
                <env name="BROADCAST_CONNECTION" value="null"/>
                <env name="CACHE_STORE" value="array"/>
                <env name="DB_CONNECTION" value="sqlite"/>
                <env name="DB_DATABASE" value=":memory:"/>
                <env name="DB_URL" value=""/>
                <env name="MAIL_MAILER" value="array"/>
                <env name="QUEUE_CONNECTION" value="sync"/>
                <env name="SESSION_DRIVER" value="array"/>
                <env name="PULSE_ENABLED" value="false"/>
                <env name="TELESCOPE_ENABLED" value="false"/>
                <env name="NIGHTWATCH_ENABLED" value="false"/>
            </php>
        </phpunit>

        XML;

    private const string FEATURE_SUITE = <<<'XML'
                <testsuite name="Feature">
                    <directory>tests/Feature</directory>
                </testsuite>

        XML;

    private const string LAYER_SUITE = <<<'XML'
                <testsuite name="functional/test-layer">
                    <directory>functional/test-layer/tests</directory>
                </testsuite>

        XML;

    private const string APP_INCLUDE = "            <directory>app</directory>\n";

    private const string LAYER_INCLUDE = "            <directory>functional/test-layer/src</directory>\n";

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/tests'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('app'));
    }

    public function test_it_adds_only_the_layer_lines_to_laravels_phpunit_xml(): void
    {
        $this->writePhpunitXml(self::LARAVEL_PHPUNIT_XML);

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame($this->laravelPhpunitXmlWithTheLayer(), $this->phpunitXml());
    }

    public function test_prune_removes_only_the_lines_of_the_removed_entries(): void
    {
        $this->writePhpunitXml(strtr($this->laravelPhpunitXmlWithTheLayer(), [
            self::LAYER_SUITE => self::LAYER_SUITE."        <testsuite name=\"functional/removed-layer\">\n            <directory>functional/removed-layer/tests</directory>\n        </testsuite>\n",
            self::LAYER_INCLUDE => self::LAYER_INCLUDE."            <directory>functional/removed-layer/src</directory>\n",
        ]));

        $this->artisan('osdd:phpunit', ['--prune' => true])->assertExitCode(0);

        $this->assertSame($this->laravelPhpunitXmlWithTheLayer(), $this->phpunitXml());
    }

    public function test_prune_leaves_an_emptied_section_without_blank_line(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('app'));
        $this->writePhpunitXml(self::LARAVEL_PHPUNIT_XML);

        $this->artisan('osdd:phpunit', ['--prune' => true])->assertExitCode(0);
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src'));
        $this->artisan('osdd:phpunit', ['--prune' => true])->assertExitCode(0);

        $this->assertStringContainsString("        <include>\n        </include>\n", $this->phpunitXml());
    }

    public function test_it_indents_the_added_lines_with_tabs_in_a_file_indented_with_tabs(): void
    {
        $this->writePhpunitXml("<?xml version=\"1.0\"?>\n<phpunit colors=\"true\">\n\t<testsuites>\n\t\t<testsuite name=\"Unit\">\n\t\t\t<directory>tests</directory>\n\t\t</testsuite>\n\t</testsuites>\n</phpunit>\n");

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<phpunit colors=\"true\">\n\t<testsuites>\n\t\t<testsuite name=\"Unit\">\n\t\t\t<directory>tests</directory>\n\t\t</testsuite>\n"
            ."\t\t<testsuite name=\"functional/test-layer\">\n\t\t\t<directory>functional/test-layer/tests</directory>\n\t\t</testsuite>\n"
            ."\t</testsuites>\n"
            ."\t<source>\n\t\t<include>\n\t\t\t<directory>functional/test-layer/src</directory>\n\t\t</include>\n\t</source>\n"
            ."</phpunit>\n",
            $this->phpunitXml(),
        );
    }

    public function test_it_indents_the_added_lines_with_two_spaces_in_a_file_indented_with_two_spaces(): void
    {
        $this->writePhpunitXml("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<phpunit>\n  <testsuites>\n    <testsuite name=\"Unit\">\n      <directory>tests</directory>\n    </testsuite>\n  </testsuites>\n  <source>\n    <include>\n      <directory suffix=\".php\">app</directory>\n    </include>\n  </source>\n</phpunit>\n");

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<phpunit>\n  <testsuites>\n    <testsuite name=\"Unit\">\n      <directory>tests</directory>\n    </testsuite>\n"
            ."    <testsuite name=\"functional/test-layer\">\n      <directory>functional/test-layer/tests</directory>\n    </testsuite>\n"
            ."  </testsuites>\n  <source>\n    <include>\n      <directory suffix=\".php\">app</directory>\n"
            ."      <directory>functional/test-layer/src</directory>\n"
            ."    </include>\n  </source>\n</phpunit>\n",
            $this->phpunitXml(),
        );
    }

    public function test_it_keeps_windows_line_endings_and_a_missing_final_newline(): void
    {
        $this->writePhpunitXml("<?xml version=\"1.0\"?>\r\n<phpunit>\r\n    <testsuites>\r\n        <testsuite name=\"functional/test-layer\">\r\n            <directory>functional/test-layer/tests</directory>\r\n        </testsuite>\r\n    </testsuites>\r\n</phpunit>");

        $this->artisan('osdd:phpunit')->assertExitCode(0);

        $this->assertSame(
            "<?xml version=\"1.0\"?>\r\n<phpunit>\r\n    <testsuites>\r\n        <testsuite name=\"functional/test-layer\">\r\n            <directory>functional/test-layer/tests</directory>\r\n        </testsuite>\r\n    </testsuites>\r\n"
            ."    <source>\r\n        <include>\r\n            <directory>functional/test-layer/src</directory>\r\n        </include>\r\n    </source>\r\n"
            .'</phpunit>',
            $this->phpunitXml(),
        );
    }

    private function laravelPhpunitXmlWithTheLayer(): string
    {
        return strtr(self::LARAVEL_PHPUNIT_XML, [
            self::FEATURE_SUITE => self::FEATURE_SUITE.self::LAYER_SUITE,
            self::APP_INCLUDE => self::APP_INCLUDE.self::LAYER_INCLUDE,
        ]);
    }

    private function writePhpunitXml(string $contents): void
    {
        $this->app['files']->put($this->app->basePath('phpunit.xml'), $contents);
    }

    private function phpunitXml(): string
    {
        return $this->app['files']->get($this->app->basePath('phpunit.xml'));
    }
}
