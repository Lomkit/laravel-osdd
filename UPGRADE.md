# Upgrade Guide

## Upgrading to 3.0 from 2.x

Version 3.0 moves the package from Xefi to Lomkit. The package is now `lomkit/laravel-osdd`, its namespace is `Lomkit\Osdd` and its service provider is `Lomkit\Osdd\OsddServiceProvider`.

Follow the steps in order. Composer boots the application (`php artisan package:discover`) right after it installs the new package, so your code must already use the new namespace when you run the update.

### Requirements

- PHP 8.3 or later.
- Laravel 13 or later. Applications on Laravel 12 stay on 2.x, or update Laravel first: `composer update laravel/framework`.

### 1. Namespace

Replace the old names across the whole project, in every folder except `vendor/`: the layers (`functional/`, `technical/` and any other path listed in `osdd.layers.paths`), `bootstrap/`, `routes/`, `tests/`, a PHPStan baseline, IDE configuration files.

| Search | Replace with |
|--------|--------------|
| `Xefi\LaravelOSDD` | `Lomkit\Osdd` |
| `Xefi\\LaravelOSDD` | `Lomkit\\Osdd` |
| `LaravelOSDDServiceProvider` | `OsddServiceProvider` |

The `Xefi\\LaravelOSDD` form appears in PHP strings and JSON files. To list the files that still mention the old names:

```bash
git grep -l -F LaravelOSDD -- . ':!vendor'
```

Every layer service provider extends the package's base class:

```diff
-use Xefi\LaravelOSDD\LayerServiceProvider;
+use Lomkit\Osdd\LayerServiceProvider;
```

The package's service provider `Xefi\LaravelOSDD\LaravelOSDDServiceProvider` is now `Lomkit\Osdd\OsddServiceProvider`. Laravel discovers it, so only an application that registers it by hand sees this name. The `technical/osdd` layer has its own provider, `Technical\Osdd\Providers\OsddServiceProvider`, unrelated to the package's one: keep its name.

### 2. Package name

Every layer requires the package in its own `composer.json`. Replace the requirement in each layer, wherever it lives:

```diff
 "require": {
-    "xefi/laravel-osdd": "*"
+    "lomkit/laravel-osdd": "*"
 }
```

Then replace it in the root `composer.json`:

```diff
 "require": {
-    "xefi/laravel-osdd": "^2.0"
+    "lomkit/laravel-osdd": "^3.0"
 }
```

If the root `composer.json` lists `xefi/laravel-osdd` under `extra.laravel.dont-discover`, replace it with `lomkit/laravel-osdd`: this list holds package names, not class names.

`lomkit/laravel-osdd` conflicts with `xefi/laravel-osdd`. If a layer still requires the old package, Composer refuses the update and names the layer.

### 3. Update

Update the package and the layers only:

```bash
composer update lomkit/laravel-osdd xefi/laravel-osdd "functional/*" "technical/*"
```

Add your other layer folders the same way, such as `"support/*"`.

While Composer removes `xefi/laravel-osdd`, Laravel prints `There was an error dispatching or handling the [...] event. Continuing with package removal...`. This is expected: the old package is already gone when Laravel handles its removal.

If you deploy with `composer install --no-scripts`, delete `bootstrap/cache/packages.php` and `bootstrap/cache/services.php` once on each server or image. Without the Composer scripts, these files keep the old provider and every `artisan` command fails with `Class "Xefi\LaravelOSDD\LaravelOSDDServiceProvider" not found`.

### Laravel Boost

Boost records the packages of your project by name in `boost.json`. Replace the entry under `packages`, then refresh the skill:

```diff
 "packages": [
-    "xefi/laravel-osdd"
+    "lomkit/laravel-osdd"
 ]
```

```bash
php artisan boost:update
```

Without this change, Boost no longer finds `xefi/laravel-osdd` and removes the skill from your project at its next update.

### Dependency bots and `composer audit`

`xefi/laravel-osdd` is abandoned in favour of `lomkit/laravel-osdd`.

- Renovate or Dependabot may offer to bump `xefi/laravel-osdd` to `^3.0`. Do not accept it: that version expects the `Lomkit\Osdd` namespace and breaks every layer that still uses `Xefi\LaravelOSDD`. Follow this guide instead.
- `composer audit` reports `xefi/laravel-osdd` as abandoned and fails by default. Switching to `lomkit/laravel-osdd` with this guide fixes it. A project that cannot switch yet can report abandoned packages without failing by setting `config.audit.abandoned` to `"report"` in its root `composer.json` (`config.policy.abandoned.audit` on Composer 2.11 and later).

### Changed behaviour

#### `overrideConfigFrom()` replaces lists

`overrideConfigFrom()` still merges maps key by key, at every depth. A list in the layer file now replaces the loaded list as a whole, and an empty array clears the loaded value. In 2.x, lists were merged index by index: overriding `['default', 'low']` with `['high']` gave `['high', 'low']`; it now gives `['high']`.

Check the config files your layers load with `overrideConfigFrom()`. Where a layer relied on the old merge to keep the tail of a list, write the full list in the layer file.

#### `osdd:layer` without a terminal

`osdd:layer` no longer runs `composer update` when it cannot ask: under `--no-interaction` (`-n`), or without a terminal (CI, scripts, AI agents). In 2.x it ran the update by default. Pass `--composer-update` to run it, or run `composer update vendor/package` yourself. `--no-composer-update` skips the question in a terminal.

Without a terminal, the command also stops asking for the rest:

- the bucket comes from the vendor of the name when it matches a key of `osdd.layers.paths` (`functional/orders` goes to the `functional` path). Pass `--target-path` for any other vendor;
- the name argument is required;
- without `--generators`, the default generators run (`migration`, `model`, `factory`, `service-provider`, `test`).

`--generators` accepts comma-separated lists (`--generators=model,policy`) as well as repeated options, and an unknown generator name now fails the command with the list of valid names. In 2.x, `--generators=model,policy` matched no generator and created a layer with only its `composer.json`.

#### `osdd:layer` refuses an existing layer

`osdd:layer` now fails when the layer directory already exists and is not empty. In 2.x it overwrote the layer's `composer.json` (dropping `extra.laravel.providers`) and its provider. Pass `--force` to rewrite the files `osdd:layer` writes: `composer.json`, the provider and the route files. The model and the controller are overwritten too; the other generated files are kept, and no second `create_*_table` migration is added.

#### `osdd:layer` validates the name

The name must be a Composer package name: `vendor/package`, lowercase letters and digits, words separated by `-`, `_` or `.`, each part starting with a letter. `osdd:layer orders` and `osdd:layer functional/My_Orders` now fail with a message instead of crashing or writing an invalid requirement into the root `composer.json`.

#### The generated layer provider no longer registers a seeder by default

The provider created by `osdd:layer` used to import and register `{Package}Seeder` even when the `seeder` generator did not run. It now registers nothing; the `seeder` generator, or `osdd:seeder`, adds `$this->loadSeeders([...])` to `boot()`. Existing layers are not changed. A seeder registered twice now runs once (the first registration and its priority win).

#### `osdd:start` asks first and runs once

`osdd:start` now asks for confirmation (default no) before it deletes `app/`, `config/` and `database/`. Scripts pass `--force`; without a terminal and without `--force`, the command fails and changes nothing. It also fails when `functional/users` or `technical/osdd` already exists, so it cannot run twice on a project. Like `osdd:layer`, it only runs `composer update` when you accept the question or pass `--composer-update`.

What it does to a fresh project also changed:

- `database/database.sqlite` (and `database/.gitignore`) are kept, so the default SQLite connection keeps working; the rest of `database/` is deleted.
- The skeleton migrations keep their names and their tables: `0001_01_01_000000_create_users_table` (users, password reset tokens, sessions) goes to `functional/users`, `0001_01_01_000001_create_cache_table` and `0001_01_01_000002_create_jobs_table` go to `technical/osdd`, whose provider now loads them. In 2.x the sessions, cache and jobs tables were lost.
- `bootstrap/providers.php` loses only the providers of the deleted `app/` directory (`App\Providers\...`); the providers of installed packages listed there stay. In 2.x the file was emptied.

Projects already converted with 2.x keep working. If yours lost the sessions, cache or jobs tables, add their migrations to a layer: `php artisan make:session-table`, `make:cache-table` and `make:queue-table` write them under `database/migrations`; move them to `technical/osdd/database/migrations` and call `$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');` in the `boot()` method of `Technical\Osdd\Providers\OsddServiceProvider`.

#### `osdd:seed` exit codes and options

`osdd:seed` now stops at the first `db:seed` that fails or is cancelled and returns its exit code; in 2.x it ignored the result and exited 0. In production without a terminal, `db:seed` cancels itself: pass the new `--force` option to `osdd:seed` (it is forwarded to `db:seed`, `migrate:fresh` and `migrate:refresh`), as you would to `db:seed`. The new `--database` option is forwarded the same way.

`--fresh` and `--refresh` together now fail; in 2.x `--refresh` was ignored.
