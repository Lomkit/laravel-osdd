<?php

namespace Lomkit\Osdd;

use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Lomkit\Osdd\Tenancy\TenantMigrationPaths;
use Lomkit\Osdd\Tenancy\TenantSeederRegistry;

abstract class LayerServiceProvider extends ServiceProvider
{
    /**
     * @param  class-string<Seeder>[]  $seeders
     * @param  int  $priority  Lower values run first; ties preserve registration order
     */
    protected function loadSeeders(array $seeders, int $priority = 0): void
    {
        $this->app->make(SeederRegistry::class)->push($priority, ...$seeders);
    }

    protected function loadTenantMigrationsFrom(string ...$paths): void
    {
        $this->app->make(TenantMigrationPaths::class)->add(...$paths);
    }

    /**
     * @param  class-string<Seeder>[]  $seeders
     * @param  int  $priority  Lower values run first; ties preserve registration order
     */
    protected function loadTenantSeeders(array $seeders, int $priority = 0): void
    {
        $this->app->make(TenantSeederRegistry::class)->push($priority, ...$seeders);
    }

    /**
     * Override an already-loaded config key with values from a file.
     *
     * Unlike mergeConfigFrom(), the layer values take priority over whatever
     * is already loaded, so a layer can override a third-party package's
     * defaults (Horizon, Telescope, …). Maps are merged key by key at every
     * depth; a list, or an empty array, replaces the loaded value as a whole.
     * Call this from register() so the override is in place before other
     * packages boot and read their config.
     */
    protected function overrideConfigFrom(string $path, string $key): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        $config = $this->app->make('config');

        /** @var array<array-key, mixed> $loaded */
        $loaded = $config->get($key, []);

        /** @var array<array-key, mixed> $overrides */
        $overrides = require $path;

        $config->set($key, $this->overrideValues($loaded, $overrides));
    }

    /**
     * @param  array<array-key, mixed>  $loaded
     * @param  array<array-key, mixed>  $overrides
     * @return array<array-key, mixed>
     */
    private function overrideValues(array $loaded, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $current = $loaded[$key] ?? null;

            $loaded[$key] = $this->isMap($value) && $this->isMap($current)
                ? $this->overrideValues($current, $value)
                : $value;
        }

        return $loaded;
    }

    /**
     * @phpstan-assert-if-true array<array-key, mixed> $value
     */
    private function isMap(mixed $value): bool
    {
        return is_array($value) && ! array_is_list($value);
    }

    /**
     * Register a layer's route files using Laravel's standard conventions —
     * mirrors `Application::configure()->withRouting(...)`:
     *
     *   - web      → wrapped in the `web` middleware group
     *   - api      → wrapped in `api` middleware + the `apiPrefix` prefix
     *   - commands → required (when running in console)
     *   - channels → required (Broadcast::channel(...) definitions)
     *   - health   → registers a GET endpoint returning {"status":"ok"}
     *
     * Once routes are cached, web, api and health come from the route cache;
     * commands and channels are still loaded, as Laravel's withRouting() does.
     *
     * For anything beyond these conventions (custom prefixes, route groups,
     * domains, ...), use Laravel's Route facade directly in boot().
     *
     * @param  list<string>|string|null  $web
     * @param  list<string>|string|null  $api
     */
    protected function withRouting(
        array|string|null $web = null,
        array|string|null $api = null,
        ?string $commands = null,
        ?string $channels = null,
        ?string $health = null,
        string $apiPrefix = 'api',
    ): void {
        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            $this->loadHttpRoutes(Arr::wrap($web), Arr::wrap($api), $health, $apiPrefix);
        }

        if (is_string($channels) && is_file($channels)) {
            require $channels;
        }

        if (is_string($commands) && is_file($commands) && $this->app->runningInConsole()) {
            require $commands;
        }
    }

    /**
     * @param  array<array-key, string>  $web
     * @param  array<array-key, string>  $api
     */
    private function loadHttpRoutes(array $web, array $api, ?string $health, string $apiPrefix): void
    {
        foreach ($web as $path) {
            if (is_file($path)) {
                Route::middleware('web')->group($path);
            }
        }

        foreach ($api as $path) {
            if (is_file($path)) {
                Route::middleware('api')->prefix($apiPrefix)->group($path);
            }
        }

        if (is_string($health)) {
            Route::get($health, fn () => response()->json(['status' => 'ok']));
        }
    }
}
