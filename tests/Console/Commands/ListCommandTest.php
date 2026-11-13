<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;
use Lomkit\Osdd\Tests\TestCase;

class ListCommandTest extends TestCase
{
    private static int $providers = 0;

    private string $projectPath;

    private string $providerClass;

    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = $this->project()->root;
        $this->providerClass = 'Functional\\Orders\\Providers\\Orders'.++self::$providers.'ServiceProvider';

        $this->writeProject();

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath.'/functional',
            'technical' => $this->projectPath.'/technical',
        ]);
    }

    public function test_the_json_output_describes_each_layer(): void
    {
        require_once $this->projectPath.'/functional/orders/src/Providers/'.Str::afterLast($this->providerClass, '\\').'.php';

        Artisan::call('osdd:list', ['--json' => true]);

        $this->assertSame([
            'layers' => [
                [
                    'name' => 'functional/orders',
                    'path' => 'functional/orders',
                    'namespace' => 'Functional\\Orders\\',
                    'providers' => [$this->providerClass],
                    'requires' => ['technical/billing'],
                    'counts' => ['models' => 2, 'migrations' => 1, 'seeders' => 1, 'tests' => 2, 'route_files' => 2],
                    'health' => ['required' => true, 'path_repository' => true, 'installed' => true, 'provider_extends_layer_service_provider' => true, 'test_suite' => true],
                ],
                [
                    'name' => 'technical/billing',
                    'path' => 'technical/billing',
                    'namespace' => 'Technical\\Billing\\',
                    'providers' => [],
                    'requires' => [],
                    'counts' => ['models' => 0, 'migrations' => 0, 'seeders' => 0, 'tests' => 0, 'route_files' => 0],
                    'health' => ['required' => false, 'path_repository' => false, 'installed' => false, 'provider_extends_layer_service_provider' => null, 'test_suite' => false],
                ],
            ],
            'invalid_manifests' => [],
        ], json_decode(Artisan::output(), true));
    }

    public function test_the_text_output_lists_the_problems(): void
    {
        $this->artisan('osdd:list')
            ->expectsOutputToContain('functional/orders')
            ->expectsOutputToContain('Functional\\Orders\\')
            ->expectsOutputToContain('not required in composer.json, no path repository, not installed, no test suite in phpunit.xml')
            ->expectsOutputToContain('2 layers listed.')
            ->assertExitCode(0);
    }

    public function test_the_provider_of_a_layer_that_is_not_installed_is_not_checked(): void
    {
        require_once $this->projectPath.'/functional/orders/src/Providers/'.Str::afterLast($this->providerClass, '\\').'.php';

        $this->writeFile('technical/billing/composer.json', json_encode([
            'name' => 'technical/billing',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Technical\\Billing\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => ['Technical\\Billing\\Providers\\BillingServiceProvider']]],
        ]));

        Artisan::call('osdd:list', ['--json' => true]);
        $billing = json_decode(Artisan::output(), true)['layers'][1];

        $this->assertSame('technical/billing', $billing['name']);
        $this->assertFalse($billing['health']['installed']);
        $this->assertNull($billing['health']['provider_extends_layer_service_provider']);

        $this->artisan('osdd:list')
            ->expectsOutputToContain('not required in composer.json, no path repository, not installed, no test suite in phpunit.xml')
            ->doesntExpectOutputToContain('provider does not extend LayerServiceProvider')
            ->assertExitCode(0);
    }

    public function test_a_layer_whose_provider_is_not_a_layer_service_provider_is_healthy(): void
    {
        $short = Str::afterLast($this->providerClass, '\\');
        $this->writeFile("functional/orders/src/Providers/{$short}.php", "<?php\n\nnamespace Functional\\Orders\\Providers;\n\nclass {$short} extends \\Illuminate\\Support\\ServiceProvider {}\n");
        require_once $this->projectPath."/functional/orders/src/Providers/{$short}.php";

        Artisan::call('osdd:list', ['--json' => true]);
        $orders = json_decode(Artisan::output(), true)['layers'][0];
        Artisan::call('osdd:list');
        $ordersText = Str::before(Artisan::output(), 'Technical\\Billing\\');

        $this->assertFalse($orders['health']['provider_extends_layer_service_provider']);
        $this->assertStringContainsString($this->providerClass.' (not a LayerServiceProvider)', $ordersText);
        $this->assertMatchesRegularExpression('/Health \.+ OK/', $ordersText);
    }

    public function test_an_unreadable_root_composer_json_counts_as_one_that_requires_nothing(): void
    {
        $this->writeFile('composer.json', '{"require": ');

        Artisan::call('osdd:list', ['--json' => true]);
        $orders = json_decode(Artisan::output(), true)['layers'][0];

        $this->assertSame('functional/orders', $orders['name']);
        $this->assertFalse($orders['health']['required']);
        $this->assertFalse($orders['health']['path_repository']);
        $this->assertTrue($orders['health']['installed']);
    }

    public function test_no_layer_prints_a_warning_and_an_empty_json_list(): void
    {
        $this->app['config']->set('osdd.layers.paths', ['functional' => $this->projectPath.'/missing']);

        $this->artisan('osdd:list')
            ->expectsOutputToContain('No layer was discovered')
            ->assertExitCode(0);

        Artisan::call('osdd:list', ['--json' => true]);
        $this->assertSame(['layers' => [], 'invalid_manifests' => []], json_decode(Artisan::output(), true));
    }

    public function test_invalid_layer_manifests_are_reported_next_to_the_layers(): void
    {
        $this->writeFile('functional/broken/composer.json', '{"name": "functional/broken", "type": "layer",');
        $this->writeFile('technical/nameless/composer.json', json_encode(['type' => 'layer']));

        Artisan::call('osdd:list', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);

        $this->assertSame(['functional/orders', 'technical/billing'], array_column($output['layers'], 'name'));
        $this->assertSame(['functional/broken/composer.json', 'technical/nameless/composer.json'], array_column($output['invalid_manifests'], 'file'));
        $this->assertStringStartsWith('it is not valid JSON', $output['invalid_manifests'][0]['error']);
        $this->assertSame('it has no "name".', $output['invalid_manifests'][1]['error']);

        $this->artisan('osdd:list')
            ->expectsOutputToContain('functional/broken/composer.json')
            ->expectsOutputToContain('INVALID')
            ->expectsOutputToContain('2 layers listed.')
            ->expectsOutputToContain('2 invalid layer manifests skipped.')
            ->assertExitCode(0);
    }

    private function writeProject(): void
    {
        $this->writeFile('composer.json', json_encode([
            'require' => ['php' => '^8.3', 'functional/orders' => '*'],
            'repositories' => [['type' => 'path', 'url' => './functional/*']],
        ]));
        $this->writeFile('vendor/composer/installed.json', json_encode(['packages' => [['name' => 'functional/orders'], ['name' => 'laravel/framework']]]));
        $this->writeFile('phpunit.xml', '<phpunit><testsuites><testsuite name="functional/orders"><directory>functional/orders/tests</directory></testsuite></testsuites></phpunit>');

        $this->writeFile('functional/orders/composer.json', json_encode([
            'name' => 'functional/orders',
            'type' => 'layer',
            'require' => ['laravel/framework' => '^13.0', 'technical/billing' => '*'],
            'autoload' => ['psr-4' => ['Functional\\Orders\\' => 'src/']],
            'extra' => ['laravel' => ['providers' => [$this->providerClass]]],
        ]));
        $short = Str::afterLast($this->providerClass, '\\');
        $this->writeFile("functional/orders/src/Providers/{$short}.php", "<?php\n\nnamespace Functional\\Orders\\Providers;\n\nclass {$short} extends \\Lomkit\\Osdd\\LayerServiceProvider {}\n");
        $this->writeFile('functional/orders/src/Models/Order.php', '<?php');
        $this->writeFile('functional/orders/src/Models/Billing/Line.php', '<?php');
        $this->writeFile('functional/orders/database/migrations/2026_01_01_000000_create_orders_table.php', '<?php');
        $this->writeFile('functional/orders/database/migrations/archive/2026_01_01_000000_create_carts_table.php', '<?php');
        $this->writeFile('functional/orders/database/seeders/OrdersSeeder.php', '<?php');
        $this->writeFile('functional/orders/tests/Feature/PlaceOrderTest.php', '<?php');
        $this->writeFile('functional/orders/tests/Unit/TotalTest.php', '<?php');
        $this->writeFile('functional/orders/tests/TestCase.php', '<?php');
        $this->writeFile('functional/orders/routes/web.php', '<?php');
        $this->writeFile('functional/orders/routes/api.php', '<?php');

        $this->writeFile('technical/billing/composer.json', json_encode([
            'name' => 'technical/billing',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Technical\\Billing\\' => 'src/']],
        ]));
    }

    private function writeFile(string $path, string|false $contents): void
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists(dirname($this->projectPath.'/'.$path));
        $files->put($this->projectPath.'/'.$path, (string) $contents);
    }
}
