<?php

namespace Lomkit\Osdd\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Lomkit\Osdd\Tests\TestCase;

class CheckCommandTest extends TestCase
{
    private string $projectPath;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = sys_get_temp_dir().'/osdd-check-'.Str::random(8);

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

    public function test_it_reports_undeclared_dependencies_and_unused_requirements_as_json(): void
    {
        $exitCode = Artisan::call('osdd:check', ['--format' => 'json']);

        $this->assertSame(1, $exitCode);
        $this->assertSame([
            'layers' => ['functional/billing', 'functional/orders', 'functional/users', 'technical/core'],
            'strict' => false,
            'undeclared' => [
                ['layer' => 'functional/orders', 'uses' => 'functional/billing', 'class' => 'Functional\\Billing\\Models\\Invoice', 'file' => 'functional/orders/src/Checkout.php', 'line' => 7],
                ['layer' => 'functional/orders', 'uses' => 'functional/billing', 'class' => 'Functional\\Billing\\format_amount', 'file' => 'functional/orders/src/Checkout.php', 'line' => 7],
                ['layer' => 'functional/orders', 'uses' => 'functional/billing', 'class' => 'Functional\\Billing\\Models\\Invoice', 'file' => 'functional/orders/tests/CheckoutTest.php', 'line' => 5],
            ],
            'unused' => [
                ['layer' => 'functional/billing', 'requires' => 'technical/core', 'section' => 'require'],
            ],
            'cycles' => [],
        ], json_decode(Artisan::output(), true));
    }

    public function test_tests_may_use_the_dev_requirements_but_sources_may_not(): void
    {
        $this->editManifest('functional/orders', fn (array $manifest): array => [...$manifest, 'require-dev' => ['functional/billing' => '*']]);

        Artisan::call('osdd:check', ['--format' => 'json']);
        $undeclared = json_decode(Artisan::output(), true)['undeclared'];

        $this->assertSame(['functional/orders/src/Checkout.php'], array_values(array_unique(array_column($undeclared, 'file'))));
    }

    public function test_strict_mode_forbids_layers_reached_through_another_requirement(): void
    {
        $this->editManifest('functional/orders', fn (array $manifest): array => [...$manifest, 'require' => [...$manifest['require'], 'functional/billing' => '*']]);

        $this->assertSame(0, Artisan::call('osdd:check', ['--format' => 'json']));
        $this->assertSame(1, Artisan::call('osdd:check', ['--format' => 'json', '--strict' => true]));

        $report = json_decode(Artisan::output(), true);
        $this->assertTrue($report['strict']);
        $this->assertSame([
            ['layer' => 'functional/orders', 'uses' => 'technical/core', 'class' => 'Technical\\Core\\Clock', 'file' => 'functional/orders/src/Checkout.php', 'line' => 9],
        ], $report['undeclared']);
    }

    public function test_it_reports_cycles_in_the_requirements(): void
    {
        $this->editManifest('functional/users', fn (array $manifest): array => [...$manifest, 'require' => [...$manifest['require'], 'functional/orders' => '*']]);

        Artisan::call('osdd:check', ['--format' => 'json']);

        $this->assertSame([['functional/orders', 'functional/users', 'functional/orders']], json_decode(Artisan::output(), true)['cycles']);
    }

    public function test_class_prefixes_of_the_ignore_list_are_not_reported(): void
    {
        $this->app['config']->set('osdd.boundaries.ignore', ['Functional\\Billing\\']);

        $this->artisan('osdd:check')
            ->expectsOutputToContain('functional/billing requires technical/core but does not use it.')
            ->expectsOutputToContain('No boundary violation in 4 layers (1 unused requirement).')
            ->assertExitCode(0);
    }

    public function test_layer_pairs_of_the_ignore_list_hide_references_unused_requirements_and_cycles(): void
    {
        $this->editManifest('functional/users', fn (array $manifest): array => [...$manifest, 'require' => [...$manifest['require'], 'functional/orders' => '*']]);
        $this->app['config']->set('osdd.boundaries.ignore', [
            ['functional/orders', 'functional/billing'],
            ['functional/billing', 'technical/core'],
            ['functional/users', 'functional/orders'],
        ]);

        Artisan::call('osdd:check', ['--format' => 'json']);
        $report = json_decode(Artisan::output(), true);

        $this->assertSame([[], [], []], [$report['undeclared'], $report['unused'], $report['cycles']]);
    }

    public function test_an_invalid_ignore_entry_fails(): void
    {
        $this->app['config']->set('osdd.boundaries.ignore', [['functional/orders']]);

        $this->artisan('osdd:check')
            ->expectsOutputToContain('Invalid osdd.boundaries.ignore entry')
            ->assertExitCode(1);
    }

    public function test_the_text_output_groups_the_references_by_layer(): void
    {
        $this->artisan('osdd:check')
            ->expectsOutputToContain('functional/orders uses functional/billing but does not require it.')
            ->expectsOutputToContain('functional/orders/src/Checkout.php:7')
            ->expectsOutputToContain('Functional\\Billing\\Models\\Invoice')
            ->expectsOutputToContain('functional/billing requires technical/core but does not use it.')
            ->expectsOutputToContain('3 boundary violations in 4 layers (1 unused requirement).')
            ->assertExitCode(1);
    }

    public function test_the_github_format_prints_workflow_annotations(): void
    {
        $this->editManifest('functional/users', fn (array $manifest): array => [...$manifest, 'require' => [...$manifest['require'], 'functional/orders' => '*']]);

        Artisan::call('osdd:check', ['--format' => 'github']);

        $this->assertSame([
            '::error file=functional/orders/src/Checkout.php,line=7,title=Undeclared layer dependency::functional/orders uses Functional\\Billing\\Models\\Invoice from functional/billing but does not require functional/billing.',
            '::error file=functional/orders/src/Checkout.php,line=7,title=Undeclared layer dependency::functional/orders uses Functional\\Billing\\format_amount from functional/billing but does not require functional/billing.',
            '::error file=functional/orders/tests/CheckoutTest.php,line=5,title=Undeclared layer dependency::functional/orders uses Functional\\Billing\\Models\\Invoice from functional/billing but does not require functional/billing.',
            '::error title=Layer cycle::Cycle in the layer requirements: functional/orders -> functional/users -> functional/orders.',
            '::warning file=functional/billing/composer.json,title=Unused layer requirement::functional/billing requires technical/core but does not use it.',
            '::warning file=functional/users/composer.json,title=Unused layer requirement::functional/users requires functional/orders but does not use it.',
            '4 boundary violations in 4 layers (2 unused requirements).',
        ], explode(PHP_EOL, trim(Artisan::output())));
    }

    public function test_the_layer_option_limits_the_check(): void
    {
        $this->assertSame(0, Artisan::call('osdd:check', ['--layer' => ['users,core'], '--format' => 'json']));
        $this->assertSame(['functional/users', 'technical/core'], json_decode(Artisan::output(), true)['layers']);

        $this->artisan('osdd:check', ['--layer' => ['shipping']])
            ->expectsOutputToContain('Layer [shipping] not found.')
            ->assertExitCode(1);
    }

    public function test_a_clean_project_passes(): void
    {
        $this->editManifest('functional/orders', fn (array $manifest): array => [...$manifest, 'require' => [...$manifest['require'], 'functional/billing' => '*']]);
        $this->editManifest('functional/billing', fn (array $manifest): array => [...$manifest, 'require' => []]);

        $this->artisan('osdd:check')
            ->expectsOutputToContain('No boundary violation in 4 layers.')
            ->assertExitCode(0);
    }

    public function test_an_unknown_format_fails(): void
    {
        $this->artisan('osdd:check', ['--format' => 'xml'])
            ->expectsOutputToContain('Unknown format [xml]. Valid formats: text, json, github.')
            ->assertExitCode(1);
    }

    private function writeProject(): void
    {
        $this->layer('technical/core', 'Technical\\Core\\', [], [
            'src/Clock.php' => "<?php\n\nnamespace Technical\\Core;\n\nclass Clock {}\n",
        ]);

        $this->layer('functional/users', 'Functional\\Users\\', ['technical/core'], [
            'src/Models/User.php' => "<?php\n\nnamespace Functional\\Users\\Models;\n\nuse Technical\\Core\\Clock;\n\nclass User {}\n",
        ]);

        $this->layer('functional/billing', 'Functional\\Billing\\', ['technical/core'], [
            'src/Models/Invoice.php' => "<?php\n\nnamespace Functional\\Billing\\Models;\n\nclass Invoice {}\n",
        ]);

        $this->layer('functional/orders', 'Functional\\Orders\\', ['functional/users', 'lomkit/laravel-osdd'], [
            'src/Checkout.php' => <<<'PHP'
                <?php

                namespace Functional\Orders;

                use Functional\Orders\Models\Order;
                use Functional\Users\Models\User;
                use Functional\Billing\{Models\Invoice, function format_amount};
                use Illuminate\Support\Str;
                use Technical\Core\Clock;
                use App\Models\Legacy;

                class Checkout
                {
                    public function run(User $user, Order $order): Invoice
                    {
                        return new \Functional\Billing\Models\Invoice;
                    }
                }
                PHP,
            'tests/CheckoutTest.php' => "<?php\n\nnamespace Functional\\Orders\\Tests;\n\nuse Functional\\Billing\\Models\\Invoice;\n\nclass CheckoutTest {}\n",
            'database/factories/OrderFactory.php' => "<?php\n\nnamespace Functional\\Orders\\Database\\Factories;\n\nuse Functional\\Users\\Models\\User;\n\nclass OrderFactory {}\n",
            'routes/web.php' => "<?php\n\nuse Functional\\Orders\\Checkout;\n",
            'vendor/acme/Leak.php' => "<?php\n\nuse Functional\\Billing\\Models\\Invoice;\n",
        ]);
    }

    /**
     * @param  list<string>  $requires
     * @param  array<string, string>  $files
     */
    private function layer(string $name, string $namespace, array $requires, array $files): void
    {
        $directory = $this->projectPath.'/'.$name;
        $filesystem = new Filesystem;
        $filesystem->ensureDirectoryExists($directory);
        $filesystem->put($directory.'/composer.json', json_encode([
            'name' => $name,
            'type' => 'layer',
            'require' => (object) array_fill_keys($requires, '*'),
            'autoload' => ['psr-4' => [$namespace => 'src/', $namespace.'Database\\Factories\\' => 'database/factories/']],
            'autoload-dev' => ['psr-4' => [$namespace.'Tests\\' => 'tests/']],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        foreach ($files as $path => $contents) {
            $filesystem->ensureDirectoryExists(dirname($directory.'/'.$path));
            $filesystem->put($directory.'/'.$path, $contents);
        }
    }

    /**
     * @param  \Closure(array<string, mixed>): array<string, mixed>  $edit
     */
    private function editManifest(string $name, \Closure $edit): void
    {
        $path = $this->projectPath.'/'.$name.'/composer.json';
        $manifest = json_decode((string) file_get_contents($path), true);

        file_put_contents($path, json_encode($edit($manifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
