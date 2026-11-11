<p align="center"><img src="https://raw.githubusercontent.com/Lomkit/art/main/laravel-osdd/cover.png" alt="Laravel OSDD: split your Laravel application into independent layers"></p>

<p align="center">
<a href="https://github.com/Lomkit/laravel-osdd/actions/workflows/tests.yml"><img src="https://github.com/Lomkit/laravel-osdd/actions/workflows/tests.yml/badge.svg?branch=main" alt="Tests"></a>
<a href="https://packagist.org/packages/lomkit/laravel-osdd"><img src="https://img.shields.io/packagist/v/lomkit/laravel-osdd" alt="Latest version"></a>
<a href="https://packagist.org/packages/lomkit/laravel-osdd"><img src="https://img.shields.io/packagist/dt/lomkit/laravel-osdd" alt="Total downloads"></a>
<a href="LICENSE"><img src="https://img.shields.io/packagist/l/lomkit/laravel-osdd" alt="License"></a>
</p>

<p align="center"><a href="https://laravel-osdd.lomkit.com"><strong>Documentation</strong></a> · <a href="UPGRADE.md">Upgrade from 2.x</a></p>

# Laravel OSDD

**One domain, one layer.**

Laravel OSDD splits your application into layers. Each layer is a Composer package of its own, with its models, migrations, seeders, tests, routes and service provider. A layer only sees the layers it requires, so the boundaries between domains stay in place as the application grows.

- **Independent layers**: every domain lives in its own package, installed through a local path repository.
- **Artisan ready**: every `make:*` command has an `osdd:*` twin that writes the file inside the layer you pick.
- **One test suite per layer**: `osdd:phpunit` keeps `phpunit.xml` in sync with your layers.
- **Seeders and config per layer**: `osdd:seed` runs the seeders every layer registers, and a layer can override the config of another package.
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

`osdd:start` creates the `functional/users` layer (the `User` model, its migration, factory and seeder) and the `technical/osdd` configuration layer, then removes `app/`, `database/` and the root `config/`.

**2. Create a layer for each domain.**

```bash
php artisan osdd:layer
```

The command asks for the bucket, the package name and the files to generate, then registers the layer in your root `composer.json`.

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

The controller lands in `functional/orders/src/Http/Controllers`, not in `app/`.

## Commands

| Command | Description |
|---------|-------------|
| `osdd:start` | Prepare a fresh Laravel project for layers |
| `osdd:layer` | Create a new layer |
| `osdd:seed` | Run the seeders registered by every layer |
| `osdd:phpunit` | Sync `phpunit.xml` with the test suite of each layer |
| `osdd:*` | The `make:*` commands, writing inside a layer |

See the [command reference](https://laravel-osdd.lomkit.com/commands) for every option.

## Contributing

Contributions are welcome: open an issue or a pull request.

## License

Laravel OSDD is open-source software licensed under the [MIT license](LICENSE).
