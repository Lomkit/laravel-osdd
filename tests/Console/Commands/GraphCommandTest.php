<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;
use Lomkit\Osdd\Tests\TestCase;

class GraphCommandTest extends TestCase
{
    private string $projectPath;

    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = $this->project()->root;

        $this->layer('functional/orders', ['functional/users' => '*', 'technical/core' => '*', 'lomkit/laravel-osdd' => '*'], ['functional/billing' => '*']);
        $this->layer('functional/users', ['technical/core' => '*'], []);
        $this->layer('functional/billing', [], []);
        $this->layer('technical/core', [], []);

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath.'/functional',
            'technical' => $this->projectPath.'/technical',
        ]);
    }

    public function test_the_default_format_is_mermaid(): void
    {
        $this->assertSame(0, Artisan::call('osdd:graph'));

        $this->assertSame(implode(PHP_EOL, [
            'graph LR',
            '    functional_billing["functional/billing"]',
            '    functional_orders["functional/orders"]',
            '    functional_users["functional/users"]',
            '    technical_core["technical/core"]',
            '    functional_orders --> functional_users',
            '    functional_orders --> technical_core',
            '    functional_users --> technical_core',
        ]), trim(Artisan::output()));
    }

    public function test_the_dot_format(): void
    {
        Artisan::call('osdd:graph', ['--format' => 'dot']);

        $this->assertSame(implode(PHP_EOL, [
            'digraph layers {',
            '    rankdir=LR;',
            '    "functional/billing";',
            '    "functional/orders";',
            '    "functional/users";',
            '    "technical/core";',
            '    "functional/orders" -> "functional/users";',
            '    "functional/orders" -> "technical/core";',
            '    "functional/users" -> "technical/core";',
            '}',
        ]), trim(Artisan::output()));
    }

    public function test_the_json_format(): void
    {
        Artisan::call('osdd:graph', ['--format' => 'json']);

        $this->assertSame([
            'nodes' => ['functional/billing', 'functional/orders', 'functional/users', 'technical/core'],
            'edges' => [
                ['from' => 'functional/orders', 'to' => 'functional/users'],
                ['from' => 'functional/orders', 'to' => 'technical/core'],
                ['from' => 'functional/users', 'to' => 'technical/core'],
            ],
        ], json_decode(Artisan::output(), true));
    }

    public function test_an_unknown_format_fails(): void
    {
        $this->artisan('osdd:graph', ['--format' => 'svg'])
            ->expectsOutputToContain('Unknown format [svg]. Valid formats: mermaid, dot, json.')
            ->assertExitCode(1);
    }

    /**
     * @param  array<string, string>  $require
     * @param  array<string, string>  $requireDev
     */
    private function layer(string $name, array $require, array $requireDev): void
    {
        $directory = $this->projectPath.'/'.$name;
        (new Filesystem)->ensureDirectoryExists($directory);
        file_put_contents($directory.'/composer.json', json_encode([
            'name' => $name,
            'type' => 'layer',
            'require' => (object) $require,
            'require-dev' => (object) $requireDev,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
