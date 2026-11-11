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

Laravel OSDD (Open Source Driven Development) splits your application into layers. Each layer is a Composer package of its own, with its models, migrations, seeders, tests, routes and service provider. Each layer declares the layers it depends on in its `composer.json`, which keeps the boundaries between domains visible as the application grows.

- **Independent layers**: every domain lives in its own package, installed through a local path repository.
- **Artisan ready**: most `make:*` class generators (`make:model`, `make:controller`, …) have an `osdd:*` twin that writes the file inside the layer you pick.
- **One test suite per layer**: `osdd:phpunit` syncs `phpunit.xml` with the test suite of each layer.
- **Seeders and config per layer**: `LayersSeeder` runs the seeders every layer registers (through `osdd:seed`, `migrate --seed` or `RefreshDatabase`), and a layer can override the config of another package.
- **Multi-tenant layers**: `--tenant` on `osdd:migration`, `osdd:seeder` and `osdd:model` keeps tenant migrations and seeders out of the central `migrate` and `osdd:seed`, and exposes them to your tenancy package (stancl/tenancy, spatie/laravel-multitenancy or your own loop).
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
php artisan osdd:layer functional/orders --generators=migration,model,factory,service-provider,test --composer-update
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

## Commands

| Command | Description |
|---------|-------------|
| `osdd:start` | Prepare a fresh Laravel project for layers |
| `osdd:layer` | Create a new layer |
| `osdd:seed` | Run the seeders registered by every layer (`Lomkit\Osdd\Seeders\LayersSeeder`) |
| `osdd:phpunit` | Sync `phpunit.xml` with the test suite and the source directory of each layer |
| `osdd:*` | Twins of most `make:*` class generators, writing inside a layer |

`php artisan about --only=osdd` lists the discovered layers, the migration paths, the number of registered seeders, and their tenant counterparts.

See the [command reference](https://laravel-osdd.lomkit.com/commands) for every option.

## Contributing

Contributions are welcome: open an issue or a pull request.

## License

Laravel OSDD is open-source software licensed under the [MIT license](LICENSE).
