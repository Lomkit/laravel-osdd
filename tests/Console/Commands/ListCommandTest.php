<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lomkit\Osdd\Tests\TestCase;

class ListCommandTest extends TestCase
{
    private string $projectPath;

    private string $providerClass;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = sys_get_temp_dir().'/osdd-list-'.Str::random(8);
        $this->providerClass = 'Functional\\Orders\\Providers\\Orders'.Str::random(8).'ServiceProvider';

        $this->writeProject();

        $app->setBasePath($this->projectPath);
        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath.'/functional',
            'technical' => $this->projectPath.'/technical',
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem)->deleteDirectory($this->projectPath);
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
                    'health' => ['required' => false, 'path_repository' => false, 'installed' => false, 'provider_extends_layer_service_provider' => false, 'test_suite' => false],
                ],
            ],
        ], json_decode(Artisan::output(), true));
    }

    public function test_the_text_output_lists_the_problems(): void
    {
        $this->artisan('osdd:list')
            ->expectsOutputToContain('functional/orders')
            ->expectsOutputToContain('Functional\\Orders\\')
            ->expectsOutputToContain('not required in composer.json, no path repository, not installed, provider does not extend LayerServiceProvider, no test suite in phpunit.xml')
            ->expectsOutputToContain('2 layers listed.')
            ->assertExitCode(0);
    }

    public function test_no_layer_prints_a_warning_and_an_empty_json_list(): void
    {
        $this->app['config']->set('osdd.layers.paths', ['functional' => $this->projectPath.'/missing']);

        $this->artisan('osdd:list')
            ->expectsOutputToContain('No layer was discovered')
            ->assertExitCode(0);

        Artisan::call('osdd:list', ['--json' => true]);
        $this->assertSame(['layers' => []], json_decode(Artisan::output(), true));
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
