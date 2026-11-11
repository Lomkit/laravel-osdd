<?php

namespace Lomkit\Osdd\Tests;

use Illuminate\Routing\Route;
use Lomkit\Osdd\LayerServiceProvider;

class LayerServiceProviderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = $this->project()->path('config-test');
        mkdir($this->tmpDir, 0755, true);
    }

    private function writeConfig(string $filename, array $values): string
    {
        $path = $this->tmpDir.'/'.$filename;
        file_put_contents($path, '<?php return '.var_export($values, true).';');

        return $path;
    }

    private function makeProvider(): LayerServiceProvider
    {
        return new class($this->app) extends LayerServiceProvider
        {
            public function boot(): void {}

            public function register(): void {}

            public function callOverrideConfigFrom(string $path, string $key): void
            {
                $this->overrideConfigFrom($path, $key);
            }

            public function callWithRouting(
                array|string|null $web = null,
                array|string|null $api = null,
                ?string $commands = null,
                ?string $channels = null,
                ?string $health = null,
                string $apiPrefix = 'api',
            ): void {
                $this->withRouting(
                    web: $web,
                    api: $api,
                    commands: $commands,
                    channels: $channels,
                    health: $health,
                    apiPrefix: $apiPrefix,
                );
            }
        };
    }

    private function writeRouteFile(string $filename, string $contents): string
    {
        $path = $this->tmpDir.'/'.$filename;
        file_put_contents($path, $contents);

        return $path;
    }

    public function test_it_overrides_an_existing_config_key(): void
    {
        $this->app['config']->set('services.stripe.key', 'original-key');

        $path = $this->writeConfig('services.php', ['stripe' => ['key' => 'layer-key']]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'services');

        $this->app->boot();

        $this->assertSame('layer-key', $this->app['config']->get('services.stripe.key'));
    }

    public function test_it_preserves_existing_keys_not_present_in_the_override(): void
    {
        $this->app['config']->set('services', [
            'stripe' => ['key' => 'original-key', 'secret' => 'original-secret'],
        ]);

        $path = $this->writeConfig('services.php', ['stripe' => ['key' => 'layer-key']]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'services');

        $this->app->boot();

        $this->assertSame('layer-key', $this->app['config']->get('services.stripe.key'));
        $this->assertSame('original-secret', $this->app['config']->get('services.stripe.secret'));
    }

    public function test_it_merges_deep_nested_keys_recursively(): void
    {
        $this->app['config']->set('package', [
            'section' => ['a' => 1, 'b' => 2],
            'other' => 'untouched',
        ]);

        $path = $this->writeConfig('package.php', ['section' => ['b' => 99, 'c' => 3]]);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'package');

        $this->app->boot();

        $this->assertSame(1, $this->app['config']->get('package.section.a'));
        $this->assertSame(99, $this->app['config']->get('package.section.b'));
        $this->assertSame(3, $this->app['config']->get('package.section.c'));
        $this->assertSame('untouched', $this->app['config']->get('package.other'));
    }

    public function test_it_replaces_lists_as_a_whole(): void
    {
        $this->app['config']->set('queue-demo', [
            'queues' => ['default', 'low'],
            'connections' => ['redis' => ['queue' => 'default', 'retry_after' => 90]],
        ]);

        $path = $this->writeConfig('queue-demo.php', [
            'queues' => ['high'],
            'connections' => ['redis' => ['queue' => 'high']],
        ]);

        $this->makeProvider()->callOverrideConfigFrom($path, 'queue-demo');

        $this->assertSame(['high'], $this->app['config']->get('queue-demo.queues'));
        $this->assertSame(['queue' => 'high', 'retry_after' => 90], $this->app['config']->get('queue-demo.connections.redis'));
    }

    public function test_an_empty_array_clears_the_loaded_value(): void
    {
        $this->app['config']->set('middleware-demo', ['except' => ['a', 'b'], 'enabled' => true]);

        $path = $this->writeConfig('middleware-demo.php', ['except' => []]);

        $this->makeProvider()->callOverrideConfigFrom($path, 'middleware-demo');

        $this->assertSame(['except' => [], 'enabled' => true], $this->app['config']->get('middleware-demo'));
    }

    public function test_it_creates_the_key_when_it_did_not_exist_before(): void
    {
        $path = $this->writeConfig('brand-new.php', ['option' => 'value']);

        $provider = $this->makeProvider();
        $provider->callOverrideConfigFrom($path, 'brand-new');

        $this->app->boot();

        $this->assertSame('value', $this->app['config']->get('brand-new.option'));
    }

    public function test_layer_aliases_are_not_registered_outside_of_tinker(): void
    {
        // tinker is never in argv in this test class — aliases must stay empty
        $this->assertNotContains('Functional\\TestLayer\\', $this->app['config']->get('tinker.alias', []));
    }

    public function test_with_routing_loads_web_routes_inside_the_web_middleware_group(): void
    {
        $path = $this->writeRouteFile(
            'web.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/dashboard', fn() => 'ok')->name('web.dashboard');\n",
        );

        $this->makeProvider()->callWithRouting(web: $path);

        $route = $this->findRouteByName('web.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('dashboard', $route->uri());
        $this->assertSame(['web'], $route->middleware());
    }

    public function test_with_routing_loads_api_routes_with_api_prefix_and_middleware(): void
    {
        $path = $this->writeRouteFile(
            'api.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/things', fn() => 'ok')->name('api.things');\n",
        );

        $this->makeProvider()->callWithRouting(api: $path);

        $route = $this->findRouteByName('api.things');

        $this->assertNotNull($route);
        $this->assertSame('api/things', $route->uri());
        $this->assertSame(['api'], $route->middleware());
    }

    public function test_with_routing_honours_a_custom_api_prefix(): void
    {
        $path = $this->writeRouteFile(
            'api.php',
            "<?php \\Illuminate\\Support\\Facades\\Route::get('/things', fn() => 'ok')->name('api.v2.things');\n",
        );

        $this->makeProvider()->callWithRouting(api: $path, apiPrefix: 'api/v2');

        $route = $this->findRouteByName('api.v2.things');

        $this->assertNotNull($route);
        $this->assertSame('api/v2/things', $route->uri());
    }

    public function test_with_routing_accepts_multiple_files_per_type(): void
    {
        $a = $this->writeRouteFile('a.php', "<?php \\Illuminate\\Support\\Facades\\Route::get('/a', fn() => 'a')->name('a');\n");
        $b = $this->writeRouteFile('b.php', "<?php \\Illuminate\\Support\\Facades\\Route::get('/b', fn() => 'b')->name('b');\n");

        $this->makeProvider()->callWithRouting(web: [$a, $b]);

        $this->assertNotNull($this->findRouteByName('a'));
        $this->assertNotNull($this->findRouteByName('b'));
    }

    public function test_with_routing_registers_a_health_endpoint(): void
    {
        $this->makeProvider()->callWithRouting(health: '/up');

        $response = $this->get('/up');

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_with_routing_requires_channels_file_so_broadcast_channels_are_registered(): void
    {
        $marker = $this->tmpDir.'/channels-loaded';
        $path = $this->writeRouteFile('channels.php', "<?php file_put_contents('{$marker}', 'yes');\n");

        $this->makeProvider()->callWithRouting(channels: $path);

        $this->assertFileExists($marker);
    }

    public function test_with_routing_loads_console_file_when_running_in_console(): void
    {
        // Testbench runs PHPUnit through Artisan, so runningInConsole() is true here.
        $marker = $this->tmpDir.'/console-loaded';
        $path = $this->writeRouteFile('console.php', "<?php file_put_contents('{$marker}', 'yes');\n");

        $this->makeProvider()->callWithRouting(commands: $path);

        $this->assertFileExists($marker);
    }

    public function test_with_routing_still_loads_commands_and_channels_when_routes_are_cached(): void
    {
        $this->app->instance('routes.cached', true);

        $consoleMarker = $this->tmpDir.'/console-loaded';
        $channelsMarker = $this->tmpDir.'/channels-loaded';
        $web = $this->writeRouteFile('web.php', "<?php\nRoute::get('/cached', fn () => 'ok')->name('web.cached');\n");
        $console = $this->writeRouteFile('console.php', "<?php file_put_contents('{$consoleMarker}', 'yes');\n");
        $channels = $this->writeRouteFile('channels.php', "<?php file_put_contents('{$channelsMarker}', 'yes');\n");

        $this->makeProvider()->callWithRouting(web: $web, commands: $console, channels: $channels, health: '/up');

        $this->assertFileExists($consoleMarker);
        $this->assertFileExists($channelsMarker);
        $this->assertNull($this->findRouteByName('web.cached'));
        $this->assertCount(0, array_filter(
            $this->app['router']->getRoutes()->getRoutes(),
            fn (Route $route): bool => $route->uri() === 'up',
        ));
    }

    public function test_with_routing_silently_skips_missing_files(): void
    {
        $this->makeProvider()->callWithRouting(
            web: $this->tmpDir.'/missing-web.php',
            api: $this->tmpDir.'/missing-api.php',
            commands: $this->tmpDir.'/missing-console.php',
            channels: $this->tmpDir.'/missing-channels.php',
        );

        $this->assertNull($this->findRouteByName('web.dashboard'));
    }

    private function findRouteByName(string $name): ?Route
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        return null;
    }
}
