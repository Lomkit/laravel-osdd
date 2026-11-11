---
name: laravel-osdd
description: Use when working in a Laravel application that depends on lomkit/laravel-osdd. This package replaces the standard app/, database/ and config/ folders with isolated "layers", each one a self-contained Composer package with its own models, migrations, seeders, tests and service provider. Trigger any time you would normally run a make:* command, write a migration, create a model, register a seeder, or add a provider — files must live inside a layer, not in app/. Also trigger when the user mentions OSDD, "layers", osdd:* artisan commands, or asks to scaffold a new domain.
---

# Laravel OSDD

`lomkit/laravel-osdd` restructures a Laravel application into independent **layers**. Each layer is its own Composer package, installed via a local path repository, with its own namespace, service provider, migrations, seeders, and tests. `osdd:start` removes the standard `app/` and root `config/` directories and empties `database/` except the SQLite database file; none of them is recreated.

## When to use this skill

Use this skill whenever `lomkit/laravel-osdd` is in the project's `composer.json`. From that point on:

- **Never** create files under `app/`, `database/migrations/`, `database/seeders/`, `database/factories/`, or root `config/`. Those directories do not exist in an OSDD project.
- **Never** run a vanilla `make:*` command (`make:model`, `make:migration`, `make:controller`, …). Always use the `osdd:*` equivalent, which targets a specific layer.
- **Never** edit `bootstrap/providers.php` to register a domain provider. Layer service providers are auto-registered via each layer's `composer.json` (`extra.laravel.providers`).
- **Never** add a `psr-4` entry for `Database\\Factories\\` or `Database\\Seeders\\` to the root `composer.json`. `osdd:start` strips them on purpose. The `App\\` mapping is kept so Laravel can still resolve an application namespace, but no code should live under `app/`.

## Features

- **Project bootstrap**: convert a fresh Laravel app into the OSDD layout (creates a starter `functional/users/` and `technical/osdd/`, then deletes `app/`, root `config/` and `database/`, keeping `database/database.sqlite`). It asks for confirmation; without a terminal it needs `--force`, which you only pass when the user asked for the conversion. Example usage:

```bash
php artisan osdd:start --force
```

- **Layer creation**: scaffold a new self-contained layer (Composer package with its own `src/`, `database/`, `tests/`, and service provider) and register it in the root `composer.json` via a wildcard path repository. The vendor of the name picks the layer path (`functional/orders` goes to `osdd.layers.paths.functional`). Example usage:

```bash
php artisan osdd:layer functional/orders --generators=migration,model,factory,provider,test --composer-update
```

- **`osdd:*` make mirrors**: every Laravel `make:*` generator has an `osdd:*` equivalent that targets a layer via `--layer=vendor/package`, or `--layer=package` when no other layer has that package name (covers `model`, `controller`, `migration`, `factory`, `seeder`, `policy`, `request`, `resource`, `test`, `config`, `view`, `class`, and more). Example usage:

```bash
php artisan osdd:model Order --layer=functional/orders --factory --migration
```

- **Layer inventory**: `osdd:list` prints every layer with its path, root namespace, providers, required layers, file counts and health flags (required in the root `composer.json`, covered by a path repository, installed, provider extends `LayerServiceProvider`, test suite in `phpunit.xml`). `--json` gives a stable machine-readable copy: use it instead of scanning directories. Example usage:

```bash
php artisan osdd:list --json
```

- **Layer boundaries**: `osdd:check` reads the PHP files of every layer and fails when a layer uses a class of another layer it does not require in its `composer.json`, or when the layer requirements form a cycle. `osdd:graph` prints the layer graph (Mermaid, DOT or JSON). Example usage:

```bash
php artisan osdd:check --format=json
```

- **Views, translations, Blade components and commands per layer**: `LayerServiceProvider::withViews()`, `withTranslations()` and `withComponents()` register a layer's `resources/views`, `lang` and `View\Components` under the layer's package name (`view('orders::index')`, `__('orders::messages.welcome')`, `<x-orders::alert />`). `osdd:view`, `osdd:component` and `osdd:command` add the matching call to the layer provider. Example usage:

```bash
php artisan osdd:component Alert --layer=functional/orders   # <x-orders::alert />
```

- **Layer-scoped seeders**: every layer registers its seeders through `LayerServiceProvider::loadSeeders([...], priority: 0)`; `Lomkit\Osdd\Seeders\LayersSeeder` runs them all across layers in priority order, and `osdd:seed` runs it (`--layer` limits it to some layers). Example usage:

```bash
php artisan osdd:seed --fresh
php artisan osdd:seed --layer=orders
php artisan migrate:fresh --seed --seeder="Lomkit\Osdd\Seeders\LayersSeeder"
```

- **Layer-scoped config**: `osdd:config` creates `{layer}/config/{name}.php` and automatically injects `$this->overrideConfigFrom(...)` into the layer's service provider. Example usage:

```bash
php artisan osdd:config orders --layer=functional/orders
```

- **Layer-scoped routing**: a generated layer provider calls `$this->withRouting(...)`, which mounts `routes/web.php` (in the `web` group), `routes/api.php` (in the `api` group, prefixed `api`), `routes/console.php`, and `routes/channels.php` — each only if the file exists. Once routes are cached, the web and api routes come from the route cache; the console and channels files are still loaded. Scaffold the route files with the `routes` generator when you create the layer. Example usage:

```bash
php artisan osdd:layer functional/orders --generators=provider,routes
```

- **PHPUnit suite sync**: `osdd:phpunit` walks every discovered layer, adds a `<testsuite>` entry for each one to the root `phpunit.xml` and adds its source directory to `<source><include>`. Idempotent; `--prune` removes the suites of deleted layers. Example usage:

```bash
php artisan osdd:phpunit
```

- **`LayerServiceProvider` base class**: every layer's own provider extends `Lomkit\Osdd\LayerServiceProvider` (not Laravel's `ServiceProvider`) to gain `loadSeeders()`, `overrideConfigFrom()`, `withRouting()`, `withViews()`, `withTranslations()` and `withComponents()`. Example usage:

```php
class OrdersServiceProvider extends \Lomkit\Osdd\LayerServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadSeeders([OrdersSeeder::class]);
    }
}
```

- **Tinker auto-aliases**: short class names (`User`, `Order`, …) are aliased to the first matching FQCN across all layers, so `User::find(1)` works without a `use` statement inside `php artisan tinker`.

### How to detect an OSDD project

Three reliable signals (any one is enough):

1. `lomkit/laravel-osdd` appears in `composer.json` under `require`.
2. A `functional/` or `technical/` directory exists at the project root with subdirectories containing `composer.json` files of `"type": "layer"`.
3. There is no `app/` directory and `bootstrap/providers.php` lists no `App\Providers\…` class.

If none of these hold, this skill does not apply — fall back to standard Laravel conventions.

## Anatomy of a layer

A layer lives in a subdirectory of one of the configured layer paths (defaults: `functional/` and `technical/`, see the package's `config/osdd.php`). The layout produced by `osdd:layer` is:

```
functional/orders/
├── composer.json                       ← "type": "layer", required
├── src/
│   ├── Models/
│   ├── Http/Controllers/
│   ├── Http/Requests/
│   ├── Policies/
│   └── Providers/OrdersServiceProvider.php
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
└── tests/
    ├── Feature/
    └── Unit/
```

Two non-negotiable rules in the layer's `composer.json`:

1. `"type": "layer"` — this is what makes the layer discovery (`Lomkit\Osdd\Layers\Layers`) find the directory. Without it the layer is invisible. The `name` must be a valid layer name and `autoload.psr-4` must map namespaces to directories: otherwise the `composer.json` is invalid, `osdd:list` shows it with its error, and the other `osdd:*` commands stop with `Invalid layer manifest [path/composer.json]: …`.
2. PSR-4 maps the **vendor + package** name in PascalCase to `src/`. For `functional/orders` that is `Functional\Orders\\` → `src/`. Sub-namespaces follow:
   - Models → `Functional\Orders\Models` (`src/Models/`)
   - Controllers → `Functional\Orders\Http\Controllers` (`src/Http/Controllers/`)
   - Service providers → `Functional\Orders\Providers` (`src/Providers/`)
   - Factories → `Functional\Orders\Database\Factories` (`database/factories/`)
   - Seeders → `Functional\Orders\Database\Seeders` (`database/seeders/`)
   - Feature tests → `Functional\Orders\Tests\Feature` (`tests/Feature/`)
   - Unit tests → `Functional\Orders\Tests\Unit` (`tests/Unit/`)

The vendor-segment of the layer name maps to the top-level config category (`functional`, `technical`). Anything under `functional/` is domain code; anything under `technical/` is cross-cutting infrastructure (auth, OSDD config, etc.).

### Layer naming rules

`osdd:layer` validates the name with Composer's package name rules: `vendor/package`, lowercase letters and digits, words separated by `-`, `_` or `.`, and each part must start with a letter so it maps to a PHP namespace. An invalid name fails with a message and writes nothing. Separators are converted to PascalCase for the namespace:

| Layer name | Namespace root | Service provider |
|---|---|---|
| `functional/orders` | `Functional\Orders` | `OrdersServiceProvider` |
| `functional/my-auth-layer` | `Functional\MyAuthLayer` | `MyAuthLayerServiceProvider` |
| `technical/feature-flags` | `Technical\FeatureFlags` | `FeatureFlagsServiceProvider` |

Pick names that read as a single noun-phrase. The package is converted via `Str::studly()` for the namespace and `Str::snake(Str::pluralStudly(...))` for the default table name (e.g. `order` → `orders`, `feature-flag` → `feature_flags`).

### What goes in `functional/` vs `technical/`

- **`functional/`** holds business domains — Users, Orders, Billing, Catalog. Each layer owns its own models, controllers, business rules, and tests. If a non-technical stakeholder would recognise the name, it belongs here.
- **`technical/`** holds cross-cutting infrastructure — the `osdd` config layer, authentication wiring, feature flags, observability, base middleware. If multiple functional layers consume it, or it has no domain meaning, it belongs here.

Don't put HTTP plumbing (controllers, requests, routes) in a `technical/` layer — domains stay self-contained. Don't duplicate the same model in two layers — if `Order` and `Customer` interact, pick the dominant domain and add a Composer `require` from the other.

## The two service providers

- **`Lomkit\Osdd\OsddServiceProvider`** — the package's own provider. Registers all `osdd:*` commands, the `Lomkit\Osdd\Layers\Layers` (the discovered layers, from `bootstrap/cache/osdd.php` when `php artisan optimize` cached them), `Lomkit\Osdd\Seeders\SeederRegistry` and `Lomkit\Osdd\Resources\ResourceNamespaces` (which view, translation and component namespace each layer provider claimed) singletons, an `OSDD` section in `php artisan about`, and (when `php artisan tinker` runs) builds class-name aliases so you can reference layer models by their short name in tinker without typing the full FQCN. When `php artisan ide-helper:models` runs, it also merges every layer's `src/` directory into `ide-helper.model_locations` (preserving existing locations and de-duplicating) so `barryvdh/laravel-ide-helper` discovers models that live inside layers. The `technical/osdd` layer has its own `Technical\Osdd\Providers\OsddServiceProvider`, unrelated to the package's: it is a layer provider like any other.

- **`Lomkit\Osdd\LayerServiceProvider`** — the abstract base class **every layer's own provider must extend** (not Laravel's `ServiceProvider`). It exposes:
  - `loadSeeders(array $seeders, int $priority = 0)` — registers seeder classes with the global registry. Lower priority runs first. This is the **only** way to make a seeder runnable by `LayersSeeder` and `osdd:seed`.
  - `overrideConfigFrom(string $path, string $key)` — like `mergeConfigFrom`, but the layer's values win over previously-loaded defaults. Maps are merged key by key at every depth; a list (or an empty array) replaces the loaded value as a whole, so `['queues' => ['high']]` gives `['high']`, not `['high', 'low']`. Used by the technical OSDD layer to override `config('osdd')` from inside the application. Skips the merge when configuration is cached.
  - `withRouting(web:, api:, commands:, channels:, health:, apiPrefix: 'api')` — mounts a layer's route files following Laravel's standard conventions: `web` files in the `web` middleware group, `api` files in the `api` group behind the `apiPrefix`, `commands`/`channels` files `require`d, and `health` registered as a GET endpoint returning `{"status":"ok"}`. Each argument is optional and missing files are skipped. Once routes are cached, `web`, `api` and `health` are skipped (they are in the cache); `commands` and `channels` are always loaded, as in Laravel's own `withRouting()`. For anything beyond these conventions (custom prefixes, domains, named groups), use the `Route` facade directly in `boot()`.
  - `withViews(string $path, ?string $namespace = null)` — `loadViewsFrom()` under `$namespace`, by default the package name of the layer that owns the provider (`orders` for `functional/orders`: the discovered layer whose `autoload.psr-4` prefix covers the provider class): `view('orders::index')`. A missing directory is skipped. A provider outside every discovered layer throws `Lomkit\Osdd\Layers\ProviderLayerNotFoundException`: pass the namespace.
  - `withTranslations(string $path, ?string $namespace = null)` — `loadTranslationsFrom()` under the same default namespace (`__('orders::messages.welcome')`) plus `loadJsonTranslationsFrom()` for the `lang/*.json` files. A missing directory is skipped.
  - `withComponents(string $namespace, ?string $prefix = null)` — `Blade::componentNamespace()`: `withComponents('Functional\\Orders\\View\\Components')` makes `<x-orders::alert />` render `Functional\Orders\View\Components\Alert`.
  - Two layers with the same package name (`functional/orders`, `technical/orders`) would share the default namespace: the second provider to register it throws `Lomkit\Osdd\Resources\ResourceNamespaceCollisionException` at boot, naming both providers. Pass the second argument in one of them (`'technical-orders'`). The generators do it for you when they see another layer with the same package name (they write `vendor-package`).

A freshly generated layer provider looks like:

```php
namespace Functional\Orders\Providers;

use Lomkit\Osdd\LayerServiceProvider;

class OrdersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }

        $this->withRouting(
            web: __DIR__ . '/../../routes/web.php',
            api: __DIR__ . '/../../routes/api.php',
            commands: __DIR__ . '/../../routes/console.php',
            channels: __DIR__ . '/../../routes/channels.php',
        );

        $this->withViews(__DIR__ . '/../../resources/views');
        $this->withTranslations(__DIR__ . '/../../lang');
    }

    public function register(): void
    {
        //
    }
}
```

When another layer already has the same package name, `osdd:layer` writes `'technical-orders'` (the full name with `-`) as second argument of `withViews()` and `withTranslations()`.

The `withRouting()` call is always present in a generated provider. The referenced route files are **not** created unless you run the `routes` generator when creating the layer (`osdd:layer … --generators=…,routes`); in an existing layer, create `routes/web.php` (or `api.php`, `console.php`, `channels.php`) by hand. `withRouting()` simply skips any path that doesn't exist, so an unused call is harmless.

### What the generator wires — and what it doesn't

The generated provider wires **migrations**, **routing** (`withRouting()`), **views** and **translations** (`withViews()`, `withTranslations()`, skipped while `resources/views` and `lang` do not exist). It registers no seeder: the `seeder` generator, like `osdd:seeder`, adds its `loadSeeders()` call. On top of that, the make commands keep the provider in sync as you generate:

- `osdd:migration` and the `osdd:*-table` commands inject `$this->loadMigrationsFrom(...)` into `boot()` (if not already present).
- `osdd:command` injects `$this->commands([\…\Console\Commands\SyncOrders::class]);` into `boot()`, so the command is registered.
- `osdd:view` injects `$this->withViews(__DIR__ . '/../../resources/views');` into `boot()` unless the provider already calls `withViews()`.
- `osdd:component` injects `withViews()` (unless `--inline`) and `$this->withComponents('Functional\\Orders\\View\\Components');` (unless `--view`), each only when the provider does not call it yet.
- `osdd:seeder` injects `$this->loadSeeders([...])` into `boot()`.
- `osdd:config` injects `$this->overrideConfigFrom(...)` into `register()`, so other packages see the override when they boot.

These injections are idempotent. The provider is the first class of the layer's `extra.laravel.providers` found on disk, else `src/Providers/{Package}ServiceProvider.php`; the `__DIR__` path is computed from its folder. When the layer has no provider, or the provider has no `boot()`/`register()` method, the command prints a warning and the exact line to add by hand: add it.

A provider written before these helpers existed (2.x) can call them by hand:

```php
public function boot(): void
{
    if ($this->app->runningInConsole()) {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadSeeders([OrdersSeeder::class]);
    }

    $this->withRouting(
        web: __DIR__ . '/../../routes/web.php',
        api: __DIR__ . '/../../routes/api.php',
    );

    $this->withViews(__DIR__ . '/../../resources/views');
    $this->withTranslations(__DIR__ . '/../../lang');
    $this->withComponents('Functional\\Orders\\View\\Components');
}
```

The `routes/` directory is created by the `routes` generator (`osdd:layer --generators=…,routes`), which scaffolds `web.php`, `api.php`, `console.php`, and `channels.php` from stubs. There is no `lang/` or `resources/views/` directory created by `osdd:layer`: `osdd:view` and `osdd:component` create `resources/views/` on demand, and `lang/` is created by hand (`lang/en/messages.php`, `lang/fr.json`).

### Tests directory

`osdd:layer --generators=test` only creates `tests/Feature/`. If you need `tests/Unit/`, generate a unit test once with `osdd:test SomeTest --unit --layer=…` — the directory will be created on first use.

## Commands

### Project bootstrap

```bash
php artisan osdd:start
```

Run **once**, immediately after `composer require lomkit/laravel-osdd`, on a fresh Laravel app. It asks for confirmation (default no) before deleting anything; without a terminal it fails unless you pass `--force`. Pass `--force` only when the user asked for the conversion. It:

1. Creates `functional/users/` (a starter Users layer with Model, Factory, Seeder, and the skeleton's `0001_01_01_000000_create_users_table` migration: `users`, `password_reset_tokens`, `sessions`). Its `UsersServiceProvider::register()` rebinds Laravel's default auth user model with `config(['auth.providers.users.model' => User::class])` so `auth()`/guards resolve the layer's `Functional\Users\Models\User`.
2. Creates `technical/osdd/` (a layer that owns the `config/osdd.php` override and the skeleton's `cache`/`cache_locks` and `jobs`/`job_batches`/`failed_jobs` migrations, loaded by `Technical\Osdd\Providers\OsddServiceProvider::boot()`). The database session, cache and queue drivers keep working after `php artisan migrate`. `Technical\Osdd\Providers\OsddServiceProvider::register()` also binds `Lomkit\Osdd\Seeders\LayersSeeder` as the default seeder (`$this->app->bind('DatabaseSeeder', LayersSeeder::class)`), so `db:seed`, `migrate --seed`, `migrate:fresh --seed` and `RefreshDatabase` with `$seed = true` run the layer seeders.
3. Deletes `app/` and root `config/`, and empties `database/` except `database/database.sqlite` and its `.gitignore`, so the default SQLite connection keeps working.
4. Strips the `Database\\Factories\\` and `Database\\Seeders\\` PSR-4 entries from the root `composer.json`. The `App\\` mapping is **kept** (so Laravel can still detect the application namespace) — no code should live under `app/`, but the mapping stays.
5. Removes the `App\Providers\…` entries from `bootstrap/providers.php` (their classes are gone; layer providers are auto-discovered). Providers of installed packages listed there stay.
6. Runs `composer update functional/users technical/osdd` when you accept the question or pass `--composer-update`; without a terminal it only prints the command.

**Destructive.** Only run on a fresh project. It refuses to run when `functional/users` or `technical/osdd` already exists.

### Create a layer

```bash
# Interactive
php artisan osdd:layer

# Non-interactive: the vendor picks the layer path (functional/… → osdd.layers.paths.functional)
php artisan osdd:layer functional/orders \
  --generators=migration,model,factory,provider,test \
  --composer-update
```

Available generators: `migration`, `model`, `factory`, `seeder`, `provider`, `test`, `controller`, `policy`, `routes`. Pass them comma-separated (`--generators=model,policy`) or repeat the option (`--generators=model --generators=policy`); an unknown name fails the command with the list of valid ones. Defaults, also used without a terminal when `--generators` is omitted: `migration, model, factory, provider, test` (note: `routes` is **not** a default — add it explicitly when the layer needs route files).

Options:

- `--target-path=/abs/path` — create the layer in that directory instead of the path named after the vendor. Required without a terminal when the vendor matches no key of `osdd.layers.paths` (the command fails and lists the keys).
- `--requires=functional/users` — add these layers to the new layer's `composer.json` `require` (`"functional/users": "*"`). Repeat the option or pass a comma-separated list; each name is resolved like `--layer` (full or package name). An unknown or ambiguous name, or the layer itself, fails the command with exit 1 before anything is written. With `--force` on an existing layer, the requirements are added to the ones it has.
- `--composer-update` / `--no-composer-update` — run or skip `composer update vendor/package` without asking. Without either, the command asks in a terminal and skips the update otherwise (it then prints the command to run).
- `--force` — overwrite an existing layer. Without it, `osdd:layer` fails when the layer directory exists and is not empty. With it, the provider and route files are rewritten, the existing `composer.json` is kept and only gains the keys of a new layer it lacks (its `require` and `extra.laravel.providers` stay), the model and controller are overwritten, the other generated files are kept, and no second `create_*_table` migration is added. To add files to an existing layer, prefer the `osdd:*` generators.

Behind the scenes `osdd:layer`:

1. Writes the layer's `composer.json` first (so make commands can discover it).
2. Runs the `provider` generator before the generators that register themselves in the provider (`migration`, `seeder`): it writes `src/Providers/{Package}ServiceProvider.php` and adds its FQCN to `extra.laravel.providers` in the layer's `composer.json`.
3. Calls each other chosen generator (e.g. `osdd:migration --create=orders --layer=functional/orders`) in the order given; `seeder` registers the seeder in the provider, and `policy` runs after `model` so it can take `--model`.
4. When both `model` and `factory` are selected, it passes `--factory` to `osdd:model` instead of running `osdd:factory` twice.
5. Registers a path repository in the root `composer.json` using a **wildcard URL** (`./functional/*`, or a `../` path for a layer path outside the project) and adds `"vendor/package": "*"` under `require` (sorted when `config.sort-packages` is on). Empty objects such as `"require-dev": {}` stay objects.
6. Runs `composer update vendor/package` when asked (see `--composer-update`).


The wildcard is intentional — every layer added to that directory will be picked up by a single repository entry; don't replace it with a per-layer URL.

### Generate code inside an existing layer

Every Laravel `make:*` generator has an `osdd:*` mirror that accepts `--layer=vendor/package`. `--layer=orders` also works when exactly one layer has the package name `orders`; when several do, the command fails and lists them (`functional/orders, technical/orders`), so pass the full name. An unknown layer fails with the list of layers and the closest name (`Layer [functional/oders] not found. Available layers: functional/orders, functional/users. Did you mean [functional/orders]?`).

If `--layer` is omitted, the command asks for the layer with a search prompt in a terminal. Without a terminal (Laravel Boost, CI, a script) or with `-n`, it fails with exit code 1 and `The --layer option is required when the command is not interactive.`, followed by the available layers: always pass `--layer`.

```bash
php artisan osdd:model Order --layer=functional/orders --factory --migration
php artisan osdd:controller OrderController --layer=functional/orders --resource --model=Order
php artisan osdd:migration add_status_to_orders_table --table=orders --layer=functional/orders
php artisan osdd:request StoreOrderRequest --layer=functional/orders
php artisan osdd:test Feature/Orders/PlaceOrderTest --layer=functional/orders
php artisan osdd:policy OrderPolicy --layer=functional/orders --model=Order
php artisan osdd:seeder OrdersSeeder --layer=functional/orders
php artisan osdd:factory OrderFactory --layer=functional/orders
php artisan osdd:config orders --layer=functional/orders
php artisan osdd:view orders.index --layer=functional/orders
php artisan osdd:component Alert --layer=functional/orders
php artisan osdd:command SyncOrders --layer=functional/orders
php artisan osdd:job-middleware RateLimitedOrders --layer=functional/orders
php artisan osdd:queue-table --layer=technical/queue
php artisan osdd:class Services/OrderPricer --layer=functional/orders
```

Full list of mirrored generators: `cache-table`, `cast`, `channel`, `class`, `command`, `component`, `config`, `controller`, `enum`, `event`, `exception`, `factory`, `interface`, `job`, `job-middleware`, `listener`, `mail`, `middleware`, `migration`, `model`, `notification`, `notifications-table`, `observer`, `policy`, `queue-batches-table`, `queue-failed-table`, `queue-table`, `request`, `resource`, `rule`, `scope`, `seeder`, `session-table`, `provider`, `test`, `trait`, `view`.

Notes on a few that diverge from vanilla Laravel:

- **`osdd:command`** — creates `src/Console/Commands/{Name}.php` and injects `$this->commands([\…\Console\Commands\{Name}::class]);` into the layer provider's `boot()` (idempotent), so the command shows in `php artisan list`.
- **`osdd:view`** — creates `{layer}/resources/views/{name}.blade.php` and injects `withViews()` when the provider does not call it yet. Render it as `view('orders::orders.index')`, never `view('orders.index')`.
- **`osdd:component Name`** — creates `src/View/Components/{Name}.php`, whose `render()` returns `view('orders::components.{name}')`, and `resources/views/components/{name}.blade.php`; injects `withViews()` and `withComponents()`. Use it as `<x-orders::name />`. `--inline` writes no view (and no `withViews()`), `--view` writes only the anonymous view (and no `withComponents()`), `--path` changes the view folder as in Laravel. The namespace is the one the booted layer already uses for its views, else the package name, else `vendor-package` when another layer has the same package name (the command then warns).
- **`osdd:job-middleware`** — writes `src/Jobs/Middleware/{Name}.php`.
- **`osdd:cache-table`, `osdd:session-table`, `osdd:queue-table`, `osdd:queue-failed-table`, `osdd:queue-batches-table`, `osdd:notifications-table`** — write the framework table migration into `{layer}/database/migrations/` (never the root `database/migrations/`) and inject `loadMigrationsFrom()`. They fail with "Migration already exists." when any layer, the root `database/migrations/` or a registered migration path already creates that table, including the skeleton files `osdd:start` copies (`0001_01_01_000000_create_users_table.php` creates `sessions`, `0001_01_01_000002_create_jobs_table.php` creates `jobs`, `job_batches` and `failed_jobs`). After `osdd:start`, the cache, jobs, batches, failed jobs and sessions tables already exist; only `osdd:notifications-table` is usually needed. They do not take the `make:*` aliases (`queue:table`, …).

- **`osdd:model`** — use the flags directly rather than the interactive "Would you like any of the following?" prompt: `--factory`, `--migration`, `--seed`, `--controller`, `--resource`, `--api`, `--requests`, `--policy`, or `--all`. When `--factory` is set, the generated model uses a custom stub that adds `use HasFactory;` and `#[UseFactory(...)]` pointing at the layer's factory namespace. Without `--factory`, the model is bare — neither the trait nor the attribute is added. **Nested models work**: `osdd:model Admin/User --layer=…` creates `src/Models/Admin/User.php` with namespace `…\Models\Admin`. On an existing model it prints "Model already exists." and, like `make:model`, asks in a terminal whether to generate the components passed as flags; without a terminal or with `-n` it generates them. `--force` overwrites the model.
- **`osdd:provider`** — creates `src/Providers/{Name}.php` and registers it in the layer's `composer.json` (`extra.laravel.providers`) only, never in `bootstrap/providers.php`.
- **`osdd:factory`** — writes `database/factories/{Name}Factory.php` with the `…\Database\Factories` namespace; `osdd:factory Order` and `osdd:factory OrderFactory` both create `OrderFactory`.
- **`osdd:config <name>`** — creates `{layer}/config/{name}.php` **and** injects `$this->overrideConfigFrom(__DIR__ . '/../../config/{name}.php', '{name}');` into the layer's service provider `register()` method, so the override is in place before other packages boot and read their config. The injection is idempotent; don't add this line by hand. If the layer has no service provider, or the provider has no `register()` method, the config file is still created and the command prints the line to add.
- **`osdd:migration`** — places the file in the resolved layer's `database/migrations/` directory and injects `$this->loadMigrationsFrom(...)` into the layer provider (idempotent). The generated provider already loads migrations, so this is usually a no-op for the standard layout.
- **`osdd:seeder`** — creates the seeder **and** injects `$this->loadSeeders([\…\YourSeeder::class]);` into the layer provider's `boot()` (idempotent). You no longer have to wire `loadSeeders()` by hand for generated seeders — but a hand-written seeder still needs the call added manually.
- **`osdd:test`** — `--unit` switches the namespace to `Tests\Unit` and the path to `tests/Unit/`; otherwise it's `Tests\Feature` / `tests/Feature/`.
- **`osdd:policy --model=Name`** — generates a full Laravel policy with type-hinted model methods (`viewAny`, `view`, `create`, …). Without `--model`, a minimal stub class is generated.
- **`--model` across layers** (`osdd:policy`, `osdd:controller`, `osdd:observer`, `osdd:factory`) — a short name (`--model=Order`) is qualified under the current layer (`…\Models\Order`); a fully qualified class of another layer or of a package (`--model='Functional\Users\Models\User'`) is kept as is. Pass the full class name to reference another layer's model.
- **`osdd:controller --requests`** — generates `Store{Model}Request` and `Update{Model}Request` in the same layer alongside the controller.
- **Calling a generator from code** — `Artisan::call('osdd:model', ['name' => 'Order', '--layer' => 'functional/orders'])` or, inside a command, `$this->call(\Lomkit\Osdd\Console\Commands\Make\ModelMakeCommand::class, [...])`. Each call writes into the layer passed to it, even when the same process calls the generator for several layers. The generator classes are `final`: do not extend them.

### Run seeders across all layers

```bash
php artisan osdd:seed
php artisan osdd:seed --fresh    # runs migrate:fresh first
php artisan osdd:seed --refresh  # runs migrate:refresh first
php artisan osdd:seed --force --database=reporting   # production, another connection
php artisan osdd:seed --layer=functional/orders --layer=billing   # only these layers
```

`--layer` takes full or package names, repeated or comma separated (`--layer=orders,billing`), resolved like the generators' `--layer` (an unknown or ambiguous name fails with exit code 1 before anything runs, `--fresh` included). Only the seeders whose class belongs to those layers run (the layer owning the longest matching psr-4 namespace), still in priority order. A selected layer with no registered seeder prints `No OSDD seeders registered for functional/orders.`.

`osdd:seed` runs `db:seed --class="Lomkit\Osdd\Seeders\LayersSeeder"` once. `LayersSeeder` reads the `SeederRegistry` and calls every registered seeder in priority order. A seeder appears in this list **only** if its layer's service provider called `$this->loadSeeders([...])`. `osdd:seeder` injects that call automatically; a **hand-written** seeder still needs it added by hand. If the output says "No OSDD seeders registered", the missing piece is a `loadSeeders()` call in the relevant `LayerServiceProvider::boot()`. A registered class that does not exist stops the run before any seeder runs (`Lomkit\Osdd\Seeders\SeederNotFoundException`), so a typo in `loadSeeders()` fails `osdd:seed`, `db:seed` and CI.

`--force` and `--database` are forwarded to `db:seed` (and to `migrate:fresh` / `migrate:refresh`). In production, `db:seed` asks for confirmation once, so without a terminal pass `--force`; a cancelled run seeds nothing and exits with 1. A seeder that throws stops the run: the next seeders do not run. `--fresh` and `--refresh` cannot be combined. A seeder registered twice runs once.

`LayersSeeder` works with Laravel's own commands and test traits too:

```bash
php artisan migrate --seed --seeder="Lomkit\Osdd\Seeders\LayersSeeder"
php artisan migrate:fresh --seed --seeder="Lomkit\Osdd\Seeders\LayersSeeder"
```

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lomkit\Osdd\Seeders\LayersSeeder;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = LayersSeeder::class;
}
```

After `osdd:start`, `LayersSeeder` is also the default seeder (see "Project bootstrap"), so `--seeder` and `$seeder` can be left out.

### Sync `phpunit.xml` with layer test suites

```bash
php artisan osdd:phpunit
```

Walks every discovered layer and:

- for each one with a `tests/` directory, adds a `<testsuite name="vendor/package"><directory>relative/path/to/tests</directory></testsuite>` entry to the root `phpunit.xml`;
- adds its source directory (`functional/orders/src`) to `<source><include>`, so code coverage covers the layers.

It also excludes `<path>/*/database/migrations` under `<source><exclude>` for each configured layer path (`functional/*/database/migrations`, `technical/*/database/migrations`), so layer migrations don't count against code coverage. Everything is idempotent — existing entries are skipped. `--prune` also removes the test suites named after a layer that no longer exists and the `<source><include>` directories that are gone (such as `app` after `osdd:start`). Run after creating or deleting a layer.

### Cache the layers

```bash
php artisan osdd:cache   # also run by php artisan optimize
php artisan osdd:clear   # also run by php artisan optimize:clear
```

`osdd:cache` writes the discovered layers to `bootstrap/cache/osdd.php`, which every process then reads instead of scanning the layer folders. It fails and writes nothing while a layer `composer.json` is invalid. Development rarely needs it: `osdd:layer`, `osdd:start`, `osdd:provider` and Composer refresh an existing cache, and a layer copied by hand needs `php artisan osdd:clear` to show up.

### Check layer boundaries

```bash
php artisan osdd:check                       # every layer, text output
php artisan osdd:check --layer=orders        # some layers (repeatable or comma separated)
php artisan osdd:check --transitive          # also allow the layers of the required layers
php artisan osdd:check --format=json         # machine-readable, for agents
php artisan osdd:check --format=github       # GitHub Actions annotations
php artisan osdd:graph --format=mermaid      # or dot, json
```

The rule: a layer may only use the classes of the layers it requires. `osdd:check` reads the `.php` files of each layer's source directory, `database/`, `routes/` and `tests/` (never `vendor/`) with the PHP tokenizer, without loading them, and collects:

- `use` imports, including group uses (`use Functional\Billing\{Models\Invoice, Models\Payment}`), aliases and `use function` / `use const`;
- fully qualified names (`\Functional\Billing\Models\Invoice::class`, `new \…`, attributes, type hints) and qualified names resolved through the imports and the current namespace (`Billing\Models\Invoice`).

Strings, comments and docblocks are ignored, and an unqualified name only counts through its `use` import. Each name belongs to the layer with the longest matching PSR-4 prefix in `autoload.psr-4` or `autoload-dev.psr-4` of every layer's `composer.json`. Names of the same layer, of `vendor/` packages, of the framework or of `App\` are not layer references and are never reported.

It reports:

- **undeclared dependencies** (error): the layer uses a layer that is not in its `require`. Files under `tests/` may also use the layers of `require-dev`. By default only the layers in the layer's own `require` are allowed: a layer reached through another requirement must be declared too, so removing a requirement elsewhere never breaks this layer silently. `--transitive` also allows the layers that the required layers require.
- **cycles** (error): layers requiring each other through `require` (`functional/orders -> functional/users -> functional/orders`).
- **unused requirements** (warning): a layer in `require` or `require-dev` that the layer's code never references.

Exit code: 1 when there is an undeclared dependency or a cycle, 0 otherwise (warnings do not fail). An unknown `--layer` or `--format` also exits with 1.

`--format=json` output:

```json
{
    "layers": ["functional/billing", "functional/orders"],
    "strict": true,
    "undeclared": [
        {"layer": "functional/orders", "uses": "functional/billing", "class": "Functional\\Billing\\Models\\Invoice", "file": "functional/orders/src/Checkout.php", "line": 7}
    ],
    "unused": [{"layer": "functional/billing", "requires": "technical/core", "section": "require"}],
    "cycles": [["functional/orders", "functional/users", "functional/orders"]]
}
```

`file` is relative to the project; each file reports a class once, at its first reference. To fix an undeclared dependency, add the used layer to the layer's `require` (or `require-dev` when only its tests use it), or move the shared code to a layer both may require. Never silence a real dependency with the ignore list.

`osdd:graph` prints the `require` edges between layers (other packages are left out), nodes and edges sorted by name. `--format=mermaid` (default) starts with `graph LR`, `--format=dot` with `digraph layers {`, and `--format=json` prints `{"nodes": [...], "edges": [{"from": "functional/orders", "to": "functional/users"}]}`.

## Configuration

The package's `config/osdd.php` (not published: the root `config/` does not exist after `osdd:start`; override it from the `technical/osdd` layer as shown below):

```php
return [
    'layers' => [
        'paths' => [
            'functional' => base_path('functional'),
            'technical'  => base_path('technical'),
        ],
    ],

    'boundaries' => [
        'ignore' => [],
    ],
];
```

`osdd.boundaries.ignore` lists what `osdd:check` does not report. Each entry is either a class prefix (a string) or a layer pair (a list of two layer names):

```php
'boundaries' => [
    'ignore' => [
        'Technical\\Shared\\Contracts\\',               // references to these classes are allowed from every layer
        ['functional/orders', 'technical/payments'],    // functional/orders may use technical/payments, its requirement is never "unused", and the edge is left out of cycles
    ],
],
```

A class prefix matches on namespace boundaries: `Functional\Billing` ignores `Functional\Billing` and everything under `Functional\Billing\`, but not `Functional\BillingReports\…`. Any other entry makes `osdd:check` fail with `Invalid osdd.boundaries.ignore entry […]`. Set it in `technical/osdd/config/osdd.php` (written by `osdd:start`, loaded with `overrideConfigFrom()`), like the other keys.

The layers are discovered once per process and resolved from the container: `app(\Lomkit\Osdd\Layers\Layers::class)` (`names()`, `find('orders')`, `owning($class)`; each `Layer` has `name`, `directory`, `rootNamespace`, `sourceDirectory`, `providers` and `requires`). Discovery reads `config('osdd.layers.paths')`, scans each path **recursively**, and treats any directory containing a `composer.json` with `"type": "layer"` as a layer. Nested layers (e.g. `functional/billing/invoices/`) are supported when the directories above them have no `composer.json`; scanning never descends into a directory that has one (a layer or any other package).

`php artisan optimize` runs `osdd:cache`, which writes the discovered layers to `bootstrap/cache/osdd.php`; `optimize:clear` runs `osdd:clear`, which deletes it. With the cache, a request reads that file instead of scanning the layer folders. `osdd:layer`, `osdd:start`, `osdd:provider` and Composer (through `package:discover`) rewrite an existing cache, and a cache built for other `osdd.layers.paths` is ignored. A layer added by hand without running Composer stays invisible until `php artisan osdd:cache` (or `osdd:clear`), as with `event:cache`.

To add a new category (e.g. `support/`), append it to `osdd.layers.paths` from inside the `technical/osdd` layer's `Technical\Osdd\Providers\OsddServiceProvider::register()` using `$this->overrideConfigFrom(...)`. Don't edit the package's own `config/osdd.php` directly.

## Idiomatic file content

The package targets Laravel 13 and uses modern attribute-based metadata on models. When writing or editing layer files by hand, match this style — the generators do.

### Model (with factory)

```php
namespace Functional\Orders\Models;

use Functional\Orders\Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reference', 'customer_id', 'total_cents', 'status'])]
#[Hidden(['internal_notes'])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'status'      => OrderStatus::class,
        ];
    }
}
```

Use `#[Fillable]`, `#[Hidden]`, `#[UseFactory]`, and the `casts()` method (not the `$casts` property). Don't manually add `$factory =` overrides — `#[UseFactory]` is the modern equivalent.

### Factory

```php
namespace Functional\Orders\Database\Factories;

use Functional\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'reference'   => fake()->unique()->bothify('ORD-#####'),
            'total_cents' => fake()->numberBetween(1_000, 100_000),
            'status'      => 'pending',
        ];
    }
}
```

### Seeder (wired in the provider)

```php
namespace Functional\Orders\Database\Seeders;

use Functional\Orders\Models\Order;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        Order::factory()->count(25)->create();
    }
}
```

The seeder is registered by the layer provider:

```php
$this->loadSeeders([OrdersSeeder::class]);
```

If you have multiple seeders, declare relative order with the second argument:

```php
$this->loadSeeders([CountriesSeeder::class], priority: -10);  // runs first
$this->loadSeeders([OrdersSeeder::class],   priority: 0);     // default
$this->loadSeeders([AuditSeeder::class],    priority: 10);    // runs last
```

Cross-layer ordering is global — if `functional/billing` depends on `functional/users` being seeded first, give the users seeder a lower priority.

### Migration

A migration is a vanilla Laravel anonymous-class migration; OSDD only changes its location to `{layer}/database/migrations/`. There is no special signature.

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();  // points at users table from another layer — fine
            $table->string('reference')->unique();
            $table->unsignedInteger('total_cents');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

Foreign keys to tables defined in other layers work as normal — all layer migrations land in the same database. The order is determined by the migration filename timestamp, which is generated at `osdd:migration` time, so create the referenced table's migration first.

### Tests

Feature tests live at `{layer}/tests/Feature/`, unit tests at `{layer}/tests/Unit/`, in the `…\Tests\Feature` / `…\Tests\Unit` namespaces. PHPUnit loads them from the test suite directory that `osdd:phpunit` adds, so the layer's `composer.json` has no autoload entry for `tests/`; the layer namespace keeps two layers from declaring the same test class.

```php
namespace Functional\Orders\Tests\Feature;

use Functional\Orders\Models\Order;
use Functional\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_place_an_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/orders', ['total_cents' => 5_000]);

        $response->assertCreated();
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }
}
```

`RefreshDatabase` runs **all** layers' migrations because they're all loaded by their respective providers — there is no per-layer database isolation at runtime. Add `protected bool $seed = true;` to also run the layer seeders through `LayersSeeder` (see "Run seeders across all layers").

## Tinker

When `php artisan tinker` runs, the package walks every layer's `src/` and registers two things:

- All layer root namespaces are added to `tinker.alias`.
- A custom autoloader maps every short class name (e.g. `User`, `Order`) to the first matching FQCN it finds across layers.

Practical consequence: you can type `User::find(1)` in tinker without `use Functional\Users\Models\User;`. If two layers define a `User` class, the first one discovered wins — disambiguate by using the FQCN.

## Navigating an existing OSDD codebase

Before adding code, locate the right layer. The `app/` and root `database/` directories don't exist — every file is under a layer.

1. **Find the layer that owns a concept.** Look for `{path}/{name}/composer.json` and check the `name` field. The layer name (`functional/orders`) tells you the path (`functional/orders/`) and namespace root (`Functional\Orders`).
2. **List all layers.** `php artisan osdd:list --json` (see "Inspecting layer state").
3. **Find where a class lives.** Search for the class name in the relevant layer's `src/`. Layers don't share code by accident — if a class isn't there, it's in another layer, in the package itself, or in `vendor/`.
4. **Find a route.** Routes are layer-local. Check each layer's `routes/` directory (`web.php`, `api.php`, `console.php`, `channels.php`) — they're mounted by the `$this->withRouting(...)` call in the layer's `LayerServiceProvider::boot()`. Routes only exist if the layer ships the file and the provider's `withRouting()` references it.
5. **Find a config key.** Layer-defined config lives in `{layer}/config/{name}.php` and is loaded into `config('{name}')` via `overrideConfigFrom`. Look at the layer's `LayerServiceProvider` (`osdd:config` injects the call into `register()`) to see what keys it overrides.

When a user asks "where should I add X?", the answer is always "in the layer responsible for X" — never `app/`. If no layer matches, the answer is "create a new layer with `osdd:layer`."

## Inspecting layer state

When debugging an OSDD project:

- **Overview** — `php artisan about --only=osdd` lists the discovered layers with their paths, the migration paths registered by the providers, and the number of registered seeders (`php artisan about --only=osdd --json` for a machine-readable copy, under the `o_s_d_d` key).
- **List discovered layers** — `php artisan osdd:list --json`. If a directory you expect isn't listed, its `composer.json` is missing `"type": "layer"`, is invalid (see `invalid_manifests`), the directory isn't under a path in `config('osdd.layers.paths')`, or a stale layer cache hides it (`php artisan osdd:clear`). The output is stable:

  ```json
  {
      "layers": [
          {
              "name": "functional/orders",
              "path": "functional/orders",
              "namespace": "Functional\\Orders\\",
              "providers": ["Functional\\Orders\\Providers\\OrdersServiceProvider"],
              "requires": ["functional/users"],
              "counts": {"models": 3, "migrations": 4, "seeders": 1, "tests": 12, "route_files": 2},
              "health": {"required": true, "path_repository": true, "installed": true, "provider_extends_layer_service_provider": true, "test_suite": false}
          }
      ],
      "invalid_manifests": [
          {"file": "functional/billing/composer.json", "error": "it has no \"name\"."}
      ]
  }
  ```

  Layers are sorted by name; `path` is relative to the project; `requires` keeps only discovered layers; `migrations` counts `database/migrations/*.php`, `tests` the `*Test.php` files under `tests/`, `route_files` `routes/*.php`. A `false` health flag tells what to fix: `required` → add `"vendor/package": "*"` to the root `require`; `path_repository` → add a `path` repository covering the layer; `installed` → `composer update vendor/package`; `provider_extends_layer_service_provider` → the layer declares no provider in `extra.laravel.providers`, a provider is not autoloadable, or it does not extend `Lomkit\Osdd\LayerServiceProvider` (`null` while the layer is not installed: its provider classes cannot be loaded yet, fix `installed` first); `test_suite` → `php artisan osdd:phpunit`. `invalid_manifests` lists the layer `composer.json` files that could not be read, relative to the project, with the reason; fix them to discover those layers. With no layer, the output is `{"layers": [], "invalid_manifests": []}`. The command never changes anything and always exits with 0.
- **List registered seeders** — `app(\Lomkit\Osdd\Seeders\SeederRegistry::class)->seeders();`. Empty? No layer provider called `loadSeeders()`.
- **List layer providers actually loaded** — `app()->getLoadedProviders()` and filter by namespace.
- **Check Composer wiring** — the root `composer.json` should have a `repositories[]` entry of type `path` with URL `./functional/*` (and/or `./technical/*`) and a `require` entry like `"functional/orders": "*"` for each layer. If a layer's classes don't autoload, run `composer dump-autoload` first; if that fails, the layer's `composer.json` is malformed.
- **Re-sync `phpunit.xml`** — run `osdd:phpunit` after creating a layer. If `vendor/bin/phpunit --testsuite=functional/orders` reports "unknown testsuite", the sync hasn't run.

## Recipes

### Bootstrap an OSDD project from scratch

```bash
composer create-project laravel/laravel my-app
cd my-app
composer require lomkit/laravel-osdd
php artisan osdd:start            # confirm, then accept the "composer update" prompt
php artisan osdd:layer functional/orders   # or interactive
php artisan migrate
```

### Add a new domain to an existing OSDD project

```bash
php artisan osdd:layer functional/billing --generators=migration,model,factory,provider,seeder,test
# Edit functional/billing/database/seeders/BillingSeeder.php as needed
php artisan osdd:phpunit          # add the layer's testsuite to phpunit.xml
php artisan osdd:seed --fresh     # seed the new layer
```

### End-to-end: add a Resource to an existing layer

For a fully wired CRUD resource (`Invoice` in `functional/billing`) — migration, model with factory, controller with form requests, policy, route, test:

```bash
# 1. Generate everything in one shot.
php artisan osdd:model Invoice --layer=functional/billing --all
#   → creates model, factory, seeder, migration, resource controller, policy, requests

# 2. Add a route. Scaffold the route files (if the layer doesn't have them yet),
#    then define the route — the generated provider's withRouting() already mounts
#    routes/web.php, so no extra wiring is needed.
#    If the layer has no routes/web.php yet, create it by hand (osdd:layer refuses an existing layer).
#    functional/billing/routes/web.php:
#      Route::resource('invoices', \Functional\Billing\Http\Controllers\InvoiceController::class);

# 3. Wire the policy. Auto-discovery doesn't cross layers — register it explicitly.
#    In BillingServiceProvider::boot():
#      Gate::policy(Invoice::class, InvoicePolicy::class);

# 4. Write a feature test.
php artisan osdd:test InvoiceCrudTest --layer=functional/billing

# 5. Run it.
php artisan osdd:phpunit
vendor/bin/phpunit --testsuite=functional/billing
```

### Register a layer's policies and observers

Policies and observers are not auto-registered across layers. Wire them in the layer's `LayerServiceProvider::boot()`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // … existing migrations/seeders …

    Gate::policy(Order::class, OrderPolicy::class);
    Order::observe(OrderObserver::class);
}
```

### Cross-layer code reference

A layer can depend on another layer's public API by typing against its FQCN. If layer A needs layer B's models or services, add `"vendor/b-package": "*"` to layer A's `composer.json` `require` (or create A with `osdd:layer vendor/a --requires=vendor/b`). Every layer shares one Composer autoloader, so PHP does not stop A from using a layer it does not require: run `php artisan osdd:check` after adding a cross-layer reference, and fix what it reports before finishing. Avoid bidirectional dependencies (`osdd:check` reports cycles); if two layers need each other, the shared concept belongs in a third layer (often under `technical/`).

### Override a layer's config from another layer

Inside a `LayerServiceProvider::register()`:

```php
$this->overrideConfigFrom(__DIR__ . '/../../config/billing.php', 'billing');
```

Use `overrideConfigFrom` (the layer base-class helper) rather than Laravel's `mergeConfigFrom` when you need the layer's values to win over previously-loaded defaults. Call it from `register()` (not `boot()`) so the override is in place before other packages boot and read their config — e.g. Horizon reading `horizon.path` while registering its routes.

## Testing workflow

OSDD doesn't replace PHPUnit — it organises test files into per-layer suites declared in `phpunit.xml`.

### After creating a layer

```bash
php artisan osdd:phpunit
```

This adds `<testsuite name="vendor/package"><directory>vendor/package/tests</directory></testsuite>` to the root `phpunit.xml`. Without this step, `vendor/bin/phpunit` won't pick up the layer's tests.

### Running tests

```bash
vendor/bin/phpunit                                 # all suites
vendor/bin/phpunit --testsuite=functional/orders   # one layer
vendor/bin/phpunit --filter=PlaceOrderTest         # one test class
```

The default Laravel `php artisan test` works too — it delegates to `phpunit` and reads the same config.

### Database in tests

Layer migrations are loaded by each layer's service provider (in `runningInConsole()` mode, which includes test runs). `use RefreshDatabase;` in a test runs every layer's migrations, in filename-timestamp order, across the entire app. There is no per-layer database isolation.

### Test namespace convention

Test classes use the layer's namespace — generators do this automatically:

- Feature: `Functional\Orders\Tests\Feature\…` in `tests/Feature/`
- Unit: `Functional\Orders\Tests\Unit\…` in `tests/Unit/`

If you write a test by hand, match the layer's namespace — a test class in `Tests\Feature\` (the default Laravel namespace) clashes with a test of the same name in another layer.

## Quick decision table

| Goal | Command |
|---|---|
| New Laravel project → OSDD layout | `osdd:start` (once, on a fresh app) |
| New domain | `osdd:layer vendor/package` |
| New model in a domain | `osdd:model Name --layer=vendor/package` |
| Migration | `osdd:migration name --layer=vendor/package` |
| Controller / request / resource | `osdd:controller / osdd:request / osdd:resource --layer=…` |
| Seeder | `osdd:seeder Name --layer=…` (adds `loadSeeders([Name::class])` to the layer provider) |
| Run all layer seeders | `osdd:seed [--fresh or --refresh] [--force] [--database=…]` |
| Run the seeders of some layers | `osdd:seed --layer=orders [--layer=billing]` |
| List layers, their contents and health | `osdd:list [--json]` |
| Check that layers only use the layers they require | `osdd:check [--layer=…] [--transitive] [--format=json]` |
| Layer dependency graph | `osdd:graph` (`--format=mermaid`, `dot` or `json`) |
| New domain that uses another one | `osdd:layer vendor/package --requires=vendor/other` |
| Show layers, migration paths, seeders | `about --only=osdd` |
| Artisan command | `osdd:command Name --layer=…` (registers it in the layer provider) |
| View / Blade component | `osdd:view name --layer=…` / `osdd:component Name --layer=…` (`<x-orders::name />`) |
| Cache, session, queue or notifications table | `osdd:cache-table` / `osdd:session-table` / `osdd:queue-table` / `osdd:queue-failed-table` / `osdd:queue-batches-table` / `osdd:notifications-table --layer=…` |
| Layer-scoped config file | `osdd:config name --layer=…` (auto-injects override) |
| Register layer test suite in phpunit.xml | `osdd:phpunit` |
| Arbitrary class | `osdd:class Path/To/Class --layer=…` |

## Pre-flight checklist before generating code

Before running any `osdd:*` make command, confirm:

1. **The layer exists.** `ls functional/{layer}/composer.json` (or `technical/`). If not, run `osdd:layer vendor/package` first.
2. **The right layer.** Re-read the request: does the concept belong in an existing layer, or is it a new domain? When in doubt, ask before creating a new layer.
3. **`--layer` is passed.** Without it the command needs a terminal for its layer prompt; without a terminal it fails with "The --layer option is required when the command is not interactive." Always pass `--layer=vendor/package` when calling artisan programmatically.
4. **No file collisions.** OSDD make commands forward to Laravel's base generators — most refuse to overwrite an existing file unless the command has a `--force` option and you pass it. Use `--force` only when you have explicitly chosen to overwrite.

## Common pitfalls

- **`make:*` instead of `osdd:*`**: the file lands in `app/` (which doesn't exist) or in a stray directory and won't be autoloaded. Always prefix with `osdd:` and pass `--layer`.
- **Omitting `--layer` in non-interactive contexts**: the command writes nothing and exits with 1 ("The --layer option is required when the command is not interactive.", followed by the available layers). Always pass `--layer=vendor/package` explicitly when scripting.
- **Forgetting `"type": "layer"` in a hand-written `composer.json`**: the layer is invisible to the layer discovery: `--layer` does not find it and `osdd:phpunit` skips it. Its provider still loads through `extra.laravel.providers` once Composer installs it. Check with `php artisan about --only=osdd`.
- **Extending `Illuminate\Support\ServiceProvider` in a layer**: works, but loses `loadSeeders()` and `overrideConfigFrom()`. Always extend `Lomkit\Osdd\LayerServiceProvider`.
- **Writing a seeder by hand without calling `loadSeeders()`**: the seeder won't run from `osdd:seed`. `osdd:seeder` injects the call automatically; a hand-written seeder needs it added to the layer provider's `boot()`.
- **A route file the provider's `withRouting()` doesn't reference**: the route silently doesn't exist at runtime — `php artisan route:list` won't show it. The generated provider's `withRouting()` only mounts the standard `routes/{web,api,console,channels}.php` paths; a route file at a non-standard path (or a regenerated provider that lost the call) needs `withRouting()` updated, or a manual `Route::...->group(...)` in `boot()`.
- **Registering a policy via `AuthServiceProvider`** (which doesn't exist in an OSDD app): use `Gate::policy()` in the owning layer's `LayerServiceProvider::boot()` instead.
- **Putting a test class under the default `Tests\` namespace**: the layer's PSR-4 won't autoload it. Always use `…\Tests\Feature` or `…\Tests\Unit` matching the layer namespace.
- **Editing the package's `config/osdd.php` directly** instead of overriding it from `technical/osdd`: changes get lost on `composer update`. Override via the technical OSDD layer.
- **Running `osdd:start` on a non-fresh project**: it deletes `app/`, `config/` and most of `database/`. Only ever run it once, on a brand-new Laravel app; it refuses to run again once `functional/users` or `technical/osdd` exists. Never pass `--force` unless the user asked for the conversion.
- **Using a class of a layer the current layer does not require**: it works at runtime (one shared autoloader) but breaks the layer contract, and `osdd:check` fails in CI. Add the layer to `require` (or `require-dev` for tests only) when you add the reference.
- **Forgetting to run `osdd:phpunit`** after creating a layer: new tests are invisible to `vendor/bin/phpunit` until the suite is added.
- **Composer not seeing a new layer**: after `osdd:layer`, run `composer update vendor/package` (or pass `--composer-update`, or accept the prompt). Without it, the layer's classes aren't installed in `vendor/composer/autoload_psr4.php`.

## Quick reference: what NEVER goes where

| If you're about to create… | …NEVER put it in… | …instead use… |
|---|---|---|
| A model | `app/Models/` | `osdd:model Name --layer=…` |
| A migration | `database/migrations/` (root) | `osdd:migration … --layer=…` |
| A controller | `app/Http/Controllers/` | `osdd:controller … --layer=…` |
| A seeder | `database/seeders/` (root) | `osdd:seeder … --layer=…` (registers it with `loadSeeders()`) |
| A factory | `database/factories/` (root) | `osdd:factory … --layer=…` |
| A config file | `config/` (root) | `osdd:config name --layer=…` |
| A service provider | `app/Providers/` + `bootstrap/providers.php` | `osdd:layer` (auto-creates one) or extend `LayerServiceProvider` inside a layer |
| A route | `routes/web.php` (root, for layer-owned routes) | `{layer}/routes/web.php` (via `osdd:layer --generators=routes`) — mounted by the provider's `withRouting()` |
| A view | `resources/views/` (root) | `osdd:view name --layer=…` (wires `withViews()`) |
| A Blade component | `app/View/Components/` | `osdd:component Name --layer=…` (wires `withViews()` and `withComponents()`) |
| A translation file | `lang/` (root) | `{layer}/lang/` + `withTranslations()` (in generated providers) |
| An Artisan command | `app/Console/Commands/` | `osdd:command Name --layer=…` (wires `$this->commands([...])`) |
| A cache / session / queue / notifications table | `make:*-table` (writes root `database/migrations/`) | `osdd:*-table --layer=…` |
| A policy | `app/Policies/` | `osdd:policy … --layer=…` + `Gate::policy()` in the provider |
