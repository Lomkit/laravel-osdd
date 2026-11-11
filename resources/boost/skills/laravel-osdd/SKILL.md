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
php artisan osdd:layer functional/orders --generators=migration,model,factory,service-provider,test --composer-update
```

- **`osdd:*` make mirrors**: most Laravel `make:*` class generators have an `osdd:*` equivalent that targets a layer via `--layer=vendor/package`, or `--layer=package` when no other layer has that package name (covers `model`, `controller`, `migration`, `factory`, `seeder`, `policy`, `request`, `resource`, `test`, `config`, `view`, `class`, and more). Example usage:

```bash
php artisan osdd:model Order --layer=functional/orders --factory --migration
```

- **Layer-scoped seeders**: every layer registers its seeders through `LayerServiceProvider::loadSeeders([...], priority: 0)`; `Lomkit\Osdd\Seeders\LayersSeeder` runs them all across layers in priority order, and `osdd:seed` runs it. Example usage:

```bash
php artisan osdd:seed --fresh
php artisan migrate:fresh --seed --seeder="Lomkit\Osdd\Seeders\LayersSeeder"
```

- **Layer-scoped config**: `osdd:config` creates `{layer}/config/{name}.php` and automatically injects `$this->overrideConfigFrom(...)` into the layer's service provider. Example usage:

```bash
php artisan osdd:config orders --layer=functional/orders
```

- **Layer-scoped routing**: a generated layer provider calls `$this->withRouting(...)`, which mounts `routes/web.php` (in the `web` group), `routes/api.php` (in the `api` group, prefixed `api`), `routes/console.php`, and `routes/channels.php` — each only if the file exists. Once routes are cached, the web and api routes come from the route cache; the console and channels files are still loaded. Scaffold the route files with the `routes` generator when you create the layer. Example usage:

```bash
php artisan osdd:layer functional/orders --generators=service-provider,routes
```

- **PHPUnit suite sync**: `osdd:phpunit` walks every discovered layer, adds a `<testsuite>` entry for each one to the root `phpunit.xml` and adds its source directory to `<source><include>`. Idempotent; `--prune` removes the suites of deleted layers. Example usage:

```bash
php artisan osdd:phpunit
```

- **`LayerServiceProvider` base class**: every layer's own provider extends `Lomkit\Osdd\LayerServiceProvider` (not Laravel's `ServiceProvider`) to gain `loadSeeders()`, `overrideConfigFrom()`, and `withRouting()`. Example usage:

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

1. `"type": "layer"` — this is what makes `LayersCollection` discover the directory. Without it the layer is invisible.
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

- **`Lomkit\Osdd\OsddServiceProvider`** — the package's own provider. Registers all `osdd:*` commands, the `SeederRegistry` singleton, an `OSDD` section in `php artisan about`, and (when `php artisan tinker` runs) builds class-name aliases so you can reference layer models by their short name in tinker without typing the full FQCN. When `php artisan ide-helper:models` runs, it also merges every layer's `src/` directory into `ide-helper.model_locations` (preserving existing locations and de-duplicating) so `barryvdh/laravel-ide-helper` discovers models that live inside layers. The `technical/osdd` layer has its own `Technical\Osdd\Providers\OsddServiceProvider`, unrelated to the package's: it is a layer provider like any other.

- **`Lomkit\Osdd\LayerServiceProvider`** — the abstract base class **every layer's own provider must extend** (not Laravel's `ServiceProvider`). It exposes:
  - `loadSeeders(array $seeders, int $priority = 0)` — registers seeder classes with the global registry. Lower priority runs first. This is the **only** way to make a seeder runnable by `LayersSeeder` and `osdd:seed`.
  - `overrideConfigFrom(string $path, string $key)` — like `mergeConfigFrom`, but the layer's values win over previously-loaded defaults. Maps are merged key by key at every depth; a list (or an empty array) replaces the loaded value as a whole, so `['queues' => ['high']]` gives `['high']`, not `['high', 'low']`. Used by the technical OSDD layer to override `config('osdd')` from inside the application. Skips the merge when configuration is cached.
  - `withRouting(web:, api:, commands:, channels:, health:, apiPrefix: 'api')` — mounts a layer's route files following Laravel's standard conventions: `web` files in the `web` middleware group, `api` files in the `api` group behind the `apiPrefix`, `commands`/`channels` files `require`d, and `health` registered as a GET endpoint returning `{"status":"ok"}`. Each argument is optional and missing files are skipped. Once routes are cached, `web`, `api` and `health` are skipped (they are in the cache); `commands` and `channels` are always loaded, as in Laravel's own `withRouting()`. For anything beyond these conventions (custom prefixes, domains, named groups), use the `Route` facade directly in `boot()`.

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
    }

    public function register(): void
    {
        //
    }
}
```

The `withRouting()` call is always present in a generated provider. The referenced route files are **not** created unless you run the `routes` generator when creating the layer (`osdd:layer … --generators=…,routes`); in an existing layer, create `routes/web.php` (or `api.php`, `console.php`, `channels.php`) by hand. `withRouting()` simply skips any path that doesn't exist, so an unused call is harmless.

### What the generator wires — and what it doesn't

The generated provider wires **migrations** and **routing** (`withRouting()`). It registers no seeder: the `seeder` generator, like `osdd:seeder`, adds its `loadSeeders()` call. On top of that, the make commands keep the provider in sync as you generate:

- `osdd:migration` injects `$this->loadMigrationsFrom(...)` into `boot()` (if not already present).
- `osdd:seeder` injects `$this->loadSeeders([...])` into `boot()`.
- `osdd:config` injects `$this->overrideConfigFrom(...)` into `register()`, so other packages see the override when they boot.

These injections are idempotent. The provider is the first class of the layer's `extra.laravel.providers` found on disk, else `src/Providers/{Package}ServiceProvider.php`; the `__DIR__` path is computed from its folder. When the layer has no provider, or the provider has no `boot()`/`register()` method, the command prints a warning and the exact line to add by hand: add it.

Views and translations are still **not** wired automatically — add them by hand when the layer has them:

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

    // Add these only when the layer has them — they are NOT generated.
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'orders');
    $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'orders');

    // Policies aren't auto-discovered across layers — register them explicitly.
    Gate::policy(Order::class, OrderPolicy::class);
}
```

The `routes/` directory is created by the `routes` generator (`osdd:layer --generators=…,routes`), which scaffolds `web.php`, `api.php`, `console.php`, and `channels.php` from stubs. There is no `lang/` or `resources/views/` directory created by `osdd:layer` — create them manually when needed (or generate views with `osdd:view`, which creates `resources/views/` on demand).

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
  --generators=migration,model,factory,service-provider,test \
  --composer-update
```

Available generators: `migration`, `model`, `factory`, `seeder`, `service-provider`, `test`, `controller`, `policy`, `routes`. Pass them comma-separated (`--generators=model,policy`) or repeat the option (`--generators=model --generators=policy`); an unknown name fails the command with the list of valid ones. Defaults, also used without a terminal when `--generators` is omitted: `migration, model, factory, service-provider, test` (note: `routes` is **not** a default — add it explicitly when the layer needs route files).

Options:

- `--target-path=/abs/path` — create the layer in that directory instead of the path named after the vendor. Required without a terminal when the vendor matches no key of `osdd.layers.paths` (the command fails and lists the keys).
- `--composer-update` / `--no-composer-update` — run or skip `composer update vendor/package` without asking. Without either, the command asks in a terminal and skips the update otherwise (it then prints the command to run).
- `--force` — overwrite an existing layer. Without it, `osdd:layer` fails when the layer directory exists and is not empty. With it, the provider and route files are rewritten, the existing `composer.json` is kept and only gains the keys of a new layer it lacks (its `require` and `extra.laravel.providers` stay), the model and controller are overwritten, the other generated files are kept, and no second `create_*_table` migration is added. To add files to an existing layer, prefer the `osdd:*` generators.

Behind the scenes `osdd:layer`:

1. Writes the layer's `composer.json` first (so make commands can discover it).
2. Generates the service provider first, from `stubs/layer/service-provider.stub`, and injects its FQCN into `extra.laravel.providers` in the layer's `composer.json`.
3. Calls each other chosen generator (e.g. `osdd:migration --create=orders --layer=functional/orders`); `seeder` registers the seeder in the provider.
4. When both `model` and `factory` are selected, it passes `--factory` to `osdd:model` instead of running `osdd:factory` twice.
5. Registers a path repository in the root `composer.json` using a **wildcard URL** (`./functional/*`, or a `../` path for a layer path outside the project) and adds `"vendor/package": "*"` under `require` (sorted when `config.sort-packages` is on). Empty objects such as `"require-dev": {}` stay objects.
6. Runs `composer update vendor/package` when asked (see `--composer-update`).


The wildcard is intentional — every layer added to that directory will be picked up by a single repository entry; don't replace it with a per-layer URL.

### Generate code inside an existing layer

Most Laravel `make:*` class generators have an `osdd:*` mirror that accepts `--layer=vendor/package`. `--layer=orders` also works when exactly one layer has the package name `orders`; when several do, the command fails and lists them (`functional/orders, technical/orders`), so pass the full name. An unknown layer fails with the list of layers and the closest name (`Layer [functional/oders] not found. Available layers: functional/orders, functional/users. Did you mean [functional/orders]?`).

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
php artisan osdd:class Services/OrderPricer --layer=functional/orders
```

Full list of mirrored generators: `cast`, `channel`, `class`, `command`, `config`, `controller`, `enum`, `event`, `exception`, `factory`, `interface`, `job`, `listener`, `mail`, `middleware`, `migration`, `model`, `notification`, `observer`, `policy`, `request`, `resource`, `rule`, `scope`, `seeder`, `provider`, `test`, `trait`, `view`.

Notes on a few that diverge from vanilla Laravel:

- **`osdd:model`** — bypasses Laravel's interactive "also create…" prompt. Use the flags directly: `--factory`, `--migration`, `--seed`, `--controller`, `--resource`, `--api`, `--requests`, `--policy`, or `--all`. When `--factory` is set, the generated model uses a custom stub that adds `use HasFactory;` and `#[UseFactory(...)]` pointing at the layer's factory namespace. Without `--factory`, the model is bare — neither the trait nor the attribute is added. **Nested models work**: `osdd:model Admin/User --layer=…` creates `src/Models/Admin/User.php` with namespace `…\Models\Admin`.
- **`osdd:config <name>`** — creates `{layer}/config/{name}.php` **and** injects `$this->overrideConfigFrom(__DIR__ . '/../../config/{name}.php', '{name}');` into the layer's service provider `register()` method, so the override is in place before other packages boot and read their config. The injection is idempotent; don't add this line by hand. If the layer has no service provider, or the provider has no `register()` method, the config file is still created and the command prints the line to add.
- **`osdd:migration`** — places the file in the resolved layer's `database/migrations/` directory and injects `$this->loadMigrationsFrom(...)` into the layer provider (idempotent). The generated provider already loads migrations, so this is usually a no-op for the standard layout.
- **`osdd:seeder`** — creates the seeder **and** injects `$this->loadSeeders([\…\YourSeeder::class]);` into the layer provider's `boot()` (idempotent). You no longer have to wire `loadSeeders()` by hand for generated seeders — but a hand-written seeder still needs the call added manually.
- **`osdd:test`** — `--unit` switches the namespace to `Tests\Unit` and the path to `tests/Unit/`; otherwise it's `Tests\Feature` / `tests/Feature/`.
- **`osdd:policy --model=Name`** — generates a full Laravel policy with type-hinted model methods (`viewAny`, `view`, `create`, …). Without `--model`, a minimal stub class is generated.
- **`--model` across layers** (`osdd:policy`, `osdd:controller`, `osdd:observer`, `osdd:factory`) — a short name (`--model=Order`) is qualified under the current layer (`…\Models\Order`); a fully qualified class of another layer or of a package (`--model='Functional\Users\Models\User'`) is kept as is. Pass the full class name to reference another layer's model.
- **`osdd:controller --requests`** — generates `Store{Model}Request` and `Update{Model}Request` in the same layer alongside the controller.

### Run seeders across all layers

```bash
php artisan osdd:seed
php artisan osdd:seed --fresh    # runs migrate:fresh first
php artisan osdd:seed --refresh  # runs migrate:refresh first
php artisan osdd:seed --force --database=tenant   # production, another connection
```

`osdd:seed` runs `db:seed --class="Lomkit\Osdd\Seeders\LayersSeeder"` once. `LayersSeeder` reads the `SeederRegistry` and calls every registered seeder in priority order. A seeder appears in this list **only** if its layer's service provider called `$this->loadSeeders([...])`. `osdd:seeder` injects that call automatically; a **hand-written** seeder still needs it added by hand. If the output says "No OSDD seeders registered", the missing piece is a `loadSeeders()` call in the relevant `LayerServiceProvider::boot()`. A registered class that does not exist is skipped with a warning.

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
];
```

`LayersCollection::fromConfig()` reads `config('osdd.layers.paths')`, scans each path **recursively**, and treats any directory containing a `composer.json` with `"type": "layer"` as a layer. Nested layers (e.g. `functional/billing/invoices/`) are supported as long as each level satisfies that contract; once a layer is matched at a given depth, scanning stops descending into it.

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
2. **List all layers.** `find functional technical -maxdepth 2 -name composer.json -exec grep -l '"type": "layer"' {} \;` (or browse the two configured directories — `functional/` and `technical/` by default).
3. **Find where a class lives.** Search for the class name in the relevant layer's `src/`. Layers don't share code by accident — if a class isn't there, it's in another layer, in the package itself, or in `vendor/`.
4. **Find a route.** Routes are layer-local. Check each layer's `routes/` directory (`web.php`, `api.php`, `console.php`, `channels.php`) — they're mounted by the `$this->withRouting(...)` call in the layer's `LayerServiceProvider::boot()`. Routes only exist if the layer ships the file and the provider's `withRouting()` references it.
5. **Find a config key.** Layer-defined config lives in `{layer}/config/{name}.php` and is loaded into `config('{name}')` via `overrideConfigFrom`. Look at the layer's `LayerServiceProvider` (`osdd:config` injects the call into `register()`) to see what keys it overrides.

When a user asks "where should I add X?", the answer is always "in the layer responsible for X" — never `app/`. If no layer matches, the answer is "create a new layer with `osdd:layer`."

## Inspecting layer state

When debugging an OSDD project:

- **Overview** — `php artisan about --only=osdd` lists the discovered layers with their paths, the migration paths registered by the providers, and the number of registered seeders (`php artisan about --only=osdd --json` for a machine-readable copy, under the `o_s_d_d` key).
- **List discovered layers** — start `tinker` and run `Lomkit\Osdd\Layers\LayersCollection::fromConfig()->map(fn($l) => $l->manifest->name())->all();`. If a directory you expect isn't listed, its `composer.json` is missing `"type": "layer"` or the directory isn't under a path in `config('osdd.layers.paths')`.
- **List registered seeders** — `app(\Lomkit\Osdd\SeederRegistry::class)->seeders();`. Empty? No layer provider called `loadSeeders()`.
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
php artisan osdd:layer functional/billing --generators=migration,model,factory,service-provider,seeder,test
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

A layer can depend on another layer's public API by typing against its FQCN — there is no special boundary mechanism, the isolation is purely organisational. If layer A needs layer B's models or services, add `"vendor/b-package": "*"` to layer A's `composer.json` `require`. Avoid bidirectional dependencies; if two layers need each other, the shared concept belongs in a third layer (often under `technical/`).

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
| Show layers, migration paths, seeders | `about --only=osdd` |
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
- **Forgetting `"type": "layer"` in a hand-written `composer.json`**: the layer is invisible to `LayersCollection`: `--layer` does not find it and `osdd:phpunit` skips it. Its provider still loads through `extra.laravel.providers` once Composer installs it. Check with `php artisan about --only=osdd`.
- **Extending `Illuminate\Support\ServiceProvider` in a layer**: works, but loses `loadSeeders()` and `overrideConfigFrom()`. Always extend `Lomkit\Osdd\LayerServiceProvider`.
- **Writing a seeder by hand without calling `loadSeeders()`**: the seeder won't run from `osdd:seed`. `osdd:seeder` injects the call automatically; a hand-written seeder needs it added to the layer provider's `boot()`.
- **A route file the provider's `withRouting()` doesn't reference**: the route silently doesn't exist at runtime — `php artisan route:list` won't show it. The generated provider's `withRouting()` only mounts the standard `routes/{web,api,console,channels}.php` paths; a route file at a non-standard path (or a regenerated provider that lost the call) needs `withRouting()` updated, or a manual `Route::...->group(...)` in `boot()`.
- **Registering a policy via `AuthServiceProvider`** (which doesn't exist in an OSDD app): use `Gate::policy()` in the owning layer's `LayerServiceProvider::boot()` instead.
- **Putting a test class under the default `Tests\` namespace**: the layer's PSR-4 won't autoload it. Always use `…\Tests\Feature` or `…\Tests\Unit` matching the layer namespace.
- **Editing the package's `config/osdd.php` directly** instead of overriding it from `technical/osdd`: changes get lost on `composer update`. Override via the technical OSDD layer.
- **Running `osdd:start` on a non-fresh project**: it deletes `app/`, `config/` and most of `database/`. Only ever run it once, on a brand-new Laravel app; it refuses to run again once `functional/users` or `technical/osdd` exists. Never pass `--force` unless the user asked for the conversion.
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
| A view | `resources/views/` (root) | `osdd:view name --layer=…` or `{layer}/resources/views/` + `loadViewsFrom` |
| A policy | `app/Policies/` | `osdd:policy … --layer=…` + `Gate::policy()` in the provider |
