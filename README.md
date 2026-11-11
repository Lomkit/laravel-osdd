<p align="center"><img src="https://raw.githubusercontent.com/Lomkit/art/main/laravel-osdd/cover.png" alt="Laravel OSDD: split your Laravel application into independent layers"></p>

<p align="center">
<a href="https://github.com/Lomkit/laravel-osdd/actions/workflows/tests.yml"><img src="https://github.com/Lomkit/laravel-osdd/actions/workflows/tests.yml/badge.svg?branch=3.x" alt="Tests"></a>
<a href="https://packagist.org/packages/lomkit/laravel-osdd"><img src="https://img.shields.io/packagist/v/lomkit/laravel-osdd" alt="Latest version"></a>
<a href="https://packagist.org/packages/lomkit/laravel-osdd"><img src="https://img.shields.io/packagist/dt/lomkit/laravel-osdd" alt="Total downloads"></a>
<a href="LICENSE"><img src="https://img.shields.io/packagist/l/lomkit/laravel-osdd" alt="License"></a>
</p>

<p align="center"><a href="https://laravel-osdd.lomkit.com"><strong>Documentation</strong></a> · <a href="UPGRADE.md">Upgrade from 2.x</a></p>

# Laravel OSDD

**One domain, one layer.**

Laravel OSDD (Open Source Driven Development) splits your application into layers. Each layer is a Composer package of its own, with its models, migrations, seeders, tests, routes and service provider. Each layer declares the layers it depends on in its `composer.json`, which keeps the boundaries between domains visible as the application grows, and `osdd:check` makes sure the code respects them.

- **Independent layers**: every domain lives in its own package, installed through a local path repository.
- **Artisan ready**: every `make:*` generator of Laravel (`make:model`, `make:component`, `make:queue-table`, …) has an `osdd:*` twin that writes the file inside the layer you pick.
- **Views, translations, components and commands per layer**: `withViews()`, `withTranslations()` and `withComponents()` register them under the layer name (`view('orders::index')`, `<x-orders::alert />`), and `osdd:view`, `osdd:component` and `osdd:command` wire what they generate into the layer provider.
- **Livewire and Filament, optional**: with Livewire 4, `withLivewire()` registers the layer components under the layer name (`<livewire:orders::cart />`) and `osdd:livewire` generates them in the layer. With Filament 5, the `Lomkit\Osdd\Filament\LayersPlugin` panel plugin discovers the resources, pages, widgets and clusters of every layer in `src/Filament/{Panel}/`, and Filament's own `make:filament-*` commands can write into a layer. Neither package is required.
- **Layer inventory**: `osdd:list` shows every layer with its contents and health (`--json` for scripts and agents). A broken layer `composer.json` is listed with its error instead of stopping the command.
- **Layer boundaries**: `osdd:check` fails when a layer uses a layer it does not require in its `composer.json`, or when layers require each other; `osdd:graph` draws the layer graph.
- **Cached in production**: `php artisan optimize` caches the discovered layers in `bootstrap/cache/osdd.php` (`osdd:cache`), so a request never scans the layer folders.
- **One test suite per layer**: `osdd:phpunit` syncs `phpunit.xml` with the test suite of each layer.
- **Layer listeners, discovered**: as Laravel does for `app/Listeners`, the listeners in each layer's `src/Listeners` are registered for the events their `handle()` method takes, and `php artisan event:cache` (or `optimize`) caches them. Set `osdd.events.discover` to `false` to register them by hand.
- **Removing a layer**: `osdd:remove` refuses a layer that another layer requires, shows what it will change, then removes the layer from the root `composer.json` and `phpunit.xml` and deletes its directory.
- **Seeders and config per layer**: `LayersSeeder` runs the seeders every layer registers (through `osdd:seed`, `migrate --seed` or `RefreshDatabase`; `osdd:seed --layer` runs the seeders of some layers only), and a layer can override the config of another package.
- **Laravel Boost**: the package ships a Boost skill, so your agent writes files inside layers, not in `app/`.

## Documentation

The full guide lives on the [documentation site](https://laravel-osdd.lomkit.com): installation, layers, every command and the API reference. Coming from 2.x? Read the [upgrade guide](UPGRADE.md).

## Requirements

PHP 8.3+ and Laravel 13.

## From app/ to layers

**1. Install the package on a fresh Laravel application.**

```bash
composer require lomkit/laravel-osdd
php artisan osdd:start
```

`osdd:start` creates the `functional/users` layer (the `User` model, its migration, factory and seeder) and the `technical/osdd` configuration layer, which also holds the cache and queue tables and makes the layer seeders the default seeder of `db:seed` and `migrate --seed`. After you confirm, it removes `app/` and the root `config/`, and empties `database/` except the SQLite database. Pass `--force` to run it from a script.

**2. Create a layer for each domain.**

```bash
php artisan osdd:layer
```

The command asks where to create the layer (functional or technical), its name and the files to generate, then registers the layer in your root `composer.json`. In a script, name the layer and pick the generators:

```bash
php artisan osdd:layer functional/orders --generators=migration,model,factory,provider,test --composer-update
```

```
functional/
  users/
  orders/
    composer.json
    src/Models/Order.php
    src/Providers/OrdersServiceProvider.php
    database/migrations/
    database/factories/
    tests/
technical/
  osdd/
```

**3. Generate code inside the layer.**

```bash
php artisan osdd:controller OrderController --layer=functional/orders
```

The controller lands in `functional/orders/src/Http/Controllers`, not in `app/`. `--layer=orders` works too while no other layer is named `orders`. Without a terminal, `--layer` is required.

**4. Keep the boundaries.**

All layers share one Composer autoloader, so PHP lets `functional/orders` use a class of `functional/billing` even when `functional/orders` does not require it. `osdd:check` closes that gap: it reads the PHP files of every layer and fails when a layer uses a layer it does not require, or when the requirements form a cycle.

```bash
php artisan osdd:layer functional/invoices --requires=functional/orders
php artisan osdd:check
```

```
  ERROR  functional/orders uses functional/billing but does not require it.

  functional/orders/src/Checkout.php:7 ............ Functional\Billing\Models\Invoice
```

Run it in CI with `php artisan osdd:check --format=github` to get the violations as annotations on the pull request, and draw the layers with `php artisan osdd:graph` (Mermaid, DOT or JSON).

## Commands

| Command | Description |
|---------|-------------|
| `osdd:start` | Prepare a fresh Laravel project for layers |
| `osdd:layer` | Create a new layer |
| `osdd:list` | List the layers with their providers, dependencies, file counts and health (`--json`) |
| `osdd:check` | Check that each layer only uses the layers it requires, and that no requirements form a cycle (`--transitive`, `--format=json` or `github`) |
| `osdd:graph` | Print the layer graph from the `require` entries (`--format=mermaid`, `dot` or `json`) |
| `osdd:seed` | Run the seeders registered by every layer (`Lomkit\Osdd\Seeders\LayersSeeder`), or by the layers given with `--layer` |
| `osdd:phpunit` | Sync `phpunit.xml` with the test suite and the source directory of each layer |
| `osdd:remove` | Remove a layer that no other layer requires: its root `require` entry, its path repository when no other package uses it, its `phpunit.xml` entries and its directory (`--keep-files`, `--force`) |
| `osdd:cache` / `osdd:clear` | Cache the discovered layers in `bootstrap/cache/osdd.php`, or remove that cache. `php artisan optimize` and `optimize:clear` run them |
| `osdd:*` | Twins of the `make:*` generators (`osdd:model`, `osdd:component`, `osdd:queue-table`, …), writing inside a layer and wiring the layer provider |
| `osdd:livewire` | Generate a Livewire component in a layer and register the layer with `withLivewire()` (only with Livewire 4 installed) |

`php artisan about --only=osdd` lists the discovered layers, the migration paths and the number of registered seeders.

See the [command reference](https://laravel-osdd.lomkit.com/commands/overview) for every option.

## Contributing

Contributions are welcome: open an issue or a pull request.

## License

Laravel OSDD is open-source software licensed under the [MIT license](LICENSE).
