<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Lomkit\Osdd\Composer\ComposerUpdate;
use Lomkit\Osdd\Console\Phpunit\PhpunitXml;
use Lomkit\Osdd\Layers\LayerCache;
use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\Fakes\FakeComposerBinary;
use Lomkit\Osdd\Tests\Fakes\FakeComposerUpdate;
use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;
use Lomkit\Osdd\Tests\Fixtures\TestbenchProcess;
use Lomkit\Osdd\Tests\TestCase;

class RemoveCommandTest extends TestCase
{
    private const string CONFIRMATION = 'Remove the layer [functional/legacy]?';

    private FakeComposerUpdate $composerUpdate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layerFixture()->bucket('technical');
        $this->layerFixture()->layer('functional/legacy', ['autoload' => ['psr-4' => ['Functional\\Legacy\\' => 'src/']]]);
        $this->layerFixture()->layer('functional/orders', ['autoload' => ['psr-4' => ['Functional\\Orders\\' => 'src/']]]);
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/legacy/src'));
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/orders/src'));
        $this->writeRootComposerJson(['functional/legacy', 'functional/orders'], ['./functional/*']);
        $this->app['files']->put($this->app->basePath('phpunit.xml'), <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit>
              <testsuites>
                <testsuite name="functional/legacy">
                  <directory>functional/legacy/tests</directory>
                </testsuite>
                <testsuite name="functional/orders">
                  <directory>functional/orders/tests</directory>
                </testsuite>
              </testsuites>
              <source>
                <include>
                  <directory suffix=".php">functional/legacy/src</directory>
                  <directory suffix=".php">functional/orders/src</directory>
                </include>
              </source>
            </phpunit>
            XML);

        $this->composerUpdate = new FakeComposerUpdate;
        $this->app->instance(ComposerUpdate::class, $this->composerUpdate);
    }

    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    public function test_it_removes_the_requirement_the_test_suite_the_coverage_source_and_the_directory(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('Layer [functional/legacy] removed.')
            ->assertExitCode(0);

        $this->assertSame(['functional/orders' => '*'], $this->rootComposerJson()['require']);
        $this->assertSame([['type' => 'path', 'url' => './functional/*']], $this->rootComposerJson()['repositories']);
        $this->assertSame(['functional/orders'], $this->phpunitXml()->testSuiteNames());
        $this->assertSame(['functional/orders/src'], $this->phpunitXml()->sourceIncludes());
        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/legacy'));
        $this->assertSame(['functional/orders'], $this->app->make(Layers::class)->names());
    }

    public function test_the_package_name_is_enough(): void
    {
        $this->artisan('osdd:remove', ['name' => 'legacy', '--force' => true, '--no-composer-update' => true])
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/legacy'));
    }

    public function test_the_path_repository_goes_when_it_covers_no_other_package(): void
    {
        $this->layerFixture()->layer('technical/logging');
        $this->writeRootComposerJson(['functional/orders', 'technical/logging'], ['./functional/*', './technical/*']);

        $this->artisan('osdd:remove', ['name' => 'technical/logging', '--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('composer.json: path repository ./technical/*')
            ->assertExitCode(0);

        $this->assertSame([['type' => 'path', 'url' => './functional/*']], $this->rootComposerJson()['repositories']);
        $this->assertSame(['functional/orders' => '*'], $this->rootComposerJson()['require']);
    }

    public function test_a_path_repository_that_still_covers_a_package_that_is_not_a_layer_stays(): void
    {
        $this->layerFixture()->layer('technical/logging');
        $this->app['files']->ensureDirectoryExists($this->app->basePath('technical/tooling'));
        $this->app['files']->put($this->app->basePath('technical/tooling/composer.json'), '{"name": "technical/tooling"}');
        $this->writeRootComposerJson(['technical/logging', 'technical/tooling'], ['./technical/*']);

        $this->artisan('osdd:remove', ['name' => 'technical/logging', '--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('still covers other packages')
            ->assertExitCode(0);

        $this->assertSame([['type' => 'path', 'url' => './technical/*']], $this->rootComposerJson()['repositories']);
        $this->assertSame(['technical/tooling' => '*'], $this->rootComposerJson()['require']);
    }

    public function test_it_refuses_a_layer_that_another_layer_requires(): void
    {
        $this->layerFixture()->layer('functional/orders', ['require' => ['functional/legacy' => '*']]);
        $this->layerFixture()->layer('technical/reports', ['require-dev' => ['functional/legacy' => '*']]);
        $before = $this->snapshot();

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true])
            ->expectsOutputToContain('Layer [functional/legacy] is required by functional/orders, technical/reports: remove it from their require or require-dev first.')
            ->assertExitCode(1);

        $this->assertSame($before, $this->snapshot());
    }

    public function test_without_force_and_without_interaction_it_only_prints_what_it_would_do(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/legacy/database/migrations'));
        $this->app['files']->put($this->app->basePath('functional/legacy/database/migrations/2026_01_01_000000_create_invoices_table.php'), '<?php');
        $before = $this->snapshot();

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--no-interaction' => true])
            ->expectsOutputToContain('Removing layer [functional/legacy]')
            ->expectsOutputToContain('composer.json: require functional/legacy')
            ->expectsOutputToContain('phpunit.xml: test suite functional/legacy')
            ->expectsOutputToContain('phpunit.xml: coverage source functional/legacy/src')
            ->expectsOutputToContain('Directory functional/legacy')
            ->expectsOutputToContain('Database tables of its 1 migration .')
            ->expectsOutputToContain('Nothing was removed. Pass --force to remove [functional/legacy] without interaction.')
            ->assertExitCode(1);

        $this->assertSame($before, $this->snapshot());
        $this->assertSame([], $this->composerUpdate->updates);
    }

    public function test_declining_the_confirmation_changes_nothing(): void
    {
        $before = $this->snapshot();

        $this->artisan('osdd:remove', ['name' => 'functional/legacy'])
            ->expectsConfirmation(self::CONFIRMATION, 'no')
            ->expectsOutputToContain('Nothing was removed.')
            ->assertExitCode(1);

        $this->assertSame($before, $this->snapshot());
    }

    public function test_confirming_removes_the_layer_and_offers_composer_update(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy'])
            ->expectsConfirmation(self::CONFIRMATION, 'yes')
            ->expectsConfirmation('Run composer update now?', 'yes')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/legacy'));
        $this->assertSame([['functional/legacy']], $this->composerUpdate->updates);
    }

    public function test_keep_files_leaves_the_directory(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--keep-files' => true, '--force' => true, '--no-composer-update' => true])
            ->assertExitCode(0);

        $this->assertFileExists($this->app->basePath('functional/legacy/composer.json'));
        $this->assertSame(['functional/orders' => '*'], $this->rootComposerJson()['require']);
        $this->assertSame(['functional/orders'], $this->phpunitXml()->testSuiteNames());
    }

    public function test_it_warns_that_the_tables_of_its_migrations_stay(): void
    {
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/legacy/database/migrations'));
        $this->app['files']->put($this->app->basePath('functional/legacy/database/migrations/2026_01_01_000000_create_invoices_table.php'), '<?php');

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('The tables created by the migrations of [functional/legacy] stay in the database')
            ->assertExitCode(0);
    }

    public function test_a_layer_without_migrations_has_no_table_warning(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->doesntExpectOutputToContain('stay in the database')
            ->assertExitCode(0);
    }

    public function test_composer_update_runs_for_the_removed_layer(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--composer-update' => true])
            ->assertExitCode(0);

        $this->assertSame([['functional/legacy']], $this->composerUpdate->updates);
    }

    public function test_a_failed_composer_update_fails_the_command(): void
    {
        $this->app->instance(ComposerUpdate::class, FakeComposerUpdate::failing());

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--composer-update' => true])
            ->expectsOutputToContain('composer update failed: run composer update functional/legacy after fixing the error above.')
            ->assertExitCode(1);
    }

    public function test_without_composer_update_it_says_how_to_uninstall_the_layer(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->expectsOutputToContain('Run composer update functional/legacy now to uninstall the layer.')
            ->assertExitCode(0);

        $this->assertSame([], $this->composerUpdate->updates);
    }

    public function test_it_refreshes_the_layer_cache(): void
    {
        $this->artisan('osdd:cache')->assertExitCode(0);

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->assertExitCode(0);

        $this->assertSame(['functional/orders'], $this->app->make(LayerCache::class)->layers()?->names());
    }

    public function test_an_unknown_layer_fails(): void
    {
        $this->artisan('osdd:remove', ['name' => 'functional/billing', '--force' => true])
            ->expectsOutputToContain('Layer [functional/billing] not found.')
            ->assertExitCode(1);
    }

    public function test_a_project_without_composer_json_nor_phpunit_xml_still_loses_the_directory(): void
    {
        $this->app['files']->delete([$this->app->basePath('composer.json'), $this->app->basePath('phpunit.xml')]);

        $this->artisan('osdd:remove', ['name' => 'functional/legacy', '--force' => true, '--no-composer-update' => true])
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/legacy'));
        $this->assertFileDoesNotExist($this->app->basePath('composer.json'));
    }

    public function test_without_a_terminal_it_needs_force(): void
    {
        $composer = new FakeComposerBinary($this->project()->path('fake-composer'));

        $refused = TestbenchProcess::run($this->project(), ['osdd:remove', 'functional/legacy'], ['COMPOSER_BINARY' => $composer->path]);

        $this->assertSame(1, $refused->getExitCode(), $refused->getOutput().$refused->getErrorOutput());
        $this->assertStringContainsString('Pass --force to remove [functional/legacy] without interaction.', $refused->getOutput());
        $this->assertDirectoryExists($this->app->basePath('functional/legacy'));

        $removed = TestbenchProcess::run($this->project(), ['osdd:remove', 'functional/legacy', '--force'], ['COMPOSER_BINARY' => $composer->path]);

        $this->assertSame(0, $removed->getExitCode(), $removed->getOutput().$removed->getErrorOutput());
        $this->assertDirectoryDoesNotExist($this->app->basePath('functional/legacy'));
        $this->assertSame([], $composer->calls());
    }

    /**
     * @param  list<string>  $layers
     * @param  list<string>  $repositories
     */
    private function writeRootComposerJson(array $layers, array $repositories): void
    {
        $this->app['files']->put($this->app->basePath('composer.json'), (string) json_encode([
            'require' => array_fill_keys($layers, '*'),
            'repositories' => array_map(fn (string $url): array => ['type' => 'path', 'url' => $url], $repositories),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function rootComposerJson(): array
    {
        return json_decode((string) file_get_contents($this->app->basePath('composer.json')), true);
    }

    private function phpunitXml(): PhpunitXml
    {
        return PhpunitXml::load($this->app->basePath('phpunit.xml'));
    }

    /**
     * @return array{string, string, bool}
     */
    private function snapshot(): array
    {
        return [
            (string) file_get_contents($this->app->basePath('composer.json')),
            (string) file_get_contents($this->app->basePath('phpunit.xml')),
            is_dir($this->app->basePath('functional/legacy')),
        ];
    }
}
