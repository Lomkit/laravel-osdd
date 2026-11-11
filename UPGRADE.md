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

`osdd:layer` no longer runs `composer update` when it cannot ask: under `--no-interaction` (`-n`), or without a terminal (CI, scripts, tools driven by Laravel Boost). In 2.x it ran the update by default. Pass `--composer-update` to run it, or run `composer update vendor/package` yourself. When the update runs and fails, `osdd:layer` and `osdd:start` now exit with 1; the layers they created stay in place. `--no-composer-update` skips the question in a terminal.

Without a terminal, the command also stops asking for the rest:

- the bucket comes from the vendor of the name when it matches a key of `osdd.layers.paths` (`functional/orders` goes to the `functional` path). Pass `--target-path` for any other vendor;
- the name argument is required;
- without `--generators`, the default generators run (`migration`, `model`, `factory`, `service-provider`, `test`).

`--generators` accepts comma-separated lists (`--generators=model,policy`) as well as repeated options, and an unknown generator name now fails the command with the list of valid names. In 2.x, `--generators=model,policy` matched no generator and created a layer with only its `composer.json`.

#### `osdd:layer` refuses an existing layer

`osdd:layer` now fails when the layer directory already exists and is not empty. In 2.x it overwrote the layer's `composer.json` (dropping `extra.laravel.providers`) and its provider. Pass `--force` to run the generators again on an existing layer: the provider and the route files are rewritten, the model and the controller are overwritten, the other generated files are kept, and no second `create_*_table` migration is added. The layer's `composer.json` is kept: it only gains the keys of a new layer that it lacks, so its `require`, `extra.laravel.providers` and your own keys stay.

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

#### `osdd:seed` runs `LayersSeeder`, exit codes and options

`osdd:seed` now runs `db:seed` once with the new `Lomkit\Osdd\Seeders\LayersSeeder`, which calls the seeders registered by the layers in priority order, and returns the exit code of `db:seed`; in 2.x it ran `db:seed` once per seeder, ignored the result and exited 0. In production, the confirmation is asked once instead of once per seeder, and a cancelled run seeds nothing. A seeder that throws stops the run. A seeder class registered with `loadSeeders()` that does not exist now fails the run before any seeder runs (`Lomkit\Osdd\Seeders\SeederNotFoundException`); 2.x skipped it with a warning. In production without a terminal, `db:seed` cancels itself: pass the new `--force` option to `osdd:seed` (it is forwarded to `db:seed`, `migrate:fresh` and `migrate:refresh`), as you would to `db:seed`. The new `--database` option is forwarded the same way.

`--fresh` and `--refresh` together now fail; in 2.x `--refresh` was ignored.

`LayersSeeder` also works with Laravel's commands and test traits: `php artisan migrate:fresh --seed --seeder="Lomkit\Osdd\Seeders\LayersSeeder"`, or `protected string $seeder = LayersSeeder::class;` next to `protected bool $seed = true;` in a test using `RefreshDatabase`. A project converted by `osdd:start` 3.0 binds it as the default seeder. To get the same on a project converted with 2.x, which has no `Database\Seeders\DatabaseSeeder`, add this line to the `register()` method of `Technical\Osdd\Providers\OsddServiceProvider`:

```php
$this->app->bind('DatabaseSeeder', \Lomkit\Osdd\Seeders\LayersSeeder::class);
```

`db:seed`, `migrate --seed`, `migrate:fresh --seed` and `RefreshDatabase` with `$seed = true` then run the layer seeders.

#### `config/osdd.php` is no longer published

`php artisan vendor:publish --provider="Lomkit\Osdd\OsddServiceProvider"` no longer copies `config/osdd.php` into the root `config/` directory, which `osdd:start` deletes. Override the package config from the `technical/osdd` layer: `technical/osdd/config/osdd.php`, loaded by `$this->overrideConfigFrom(...)` in `Technical\Osdd\Providers\OsddServiceProvider::register()`. If your project still has a root `config/osdd.php`, move its values to `technical/osdd/config/osdd.php` and delete it.

#### Generators report an unresolved `--layer` and exit with 1

The `osdd:*` generators no longer throw `Lomkit\Osdd\Layers\LayerNotFoundException` for an unknown `--layer`: they print one error, which lists the available layers and the closest name, and exit with code 1. Code that calls them with `Artisan::call()` and catches the exception must check the exit code instead. Without a terminal and without `--layer`, they now fail with "The --layer option is required when the command is not interactive." instead of a bare "Required.".

`--layer` also accepts the package name alone when one layer has it: `--layer=orders` for `functional/orders`. When two layers share it (`functional/orders` and `technical/orders`), pass the full name.

#### `osdd:phpunit` coverage source

`osdd:phpunit` now adds each layer's source directory to `<source><include>` and excludes the migrations per layer path (`functional/*/database/migrations`) instead of `**/database/migrations`, which walked the whole project, `vendor/` included. Remove the old entry from your `phpunit.xml`:

```diff
 <exclude>
-    <directory suffix=".php">**/database/migrations</directory>
 </exclude>
```

The new `--prune` option removes the test suites of layers that no longer exist and the `<source><include>` directories that are gone, such as `app` after `osdd:start`.

#### Other changes in the generators and the base provider

These fix 2.x bugs; existing layers keep working without changes.

- `withRouting()` now loads the `commands` and `channels` files when routes are cached (after `php artisan optimize`), as Laravel does. In 2.x, the `routes/console.php` commands and schedules and the broadcast channels of every layer disappeared once routes were cached. If you worked around it by loading these files yourself, remove the workaround.
- `osdd:config` injects `overrideConfigFrom()` into `register()` instead of `boot()`. Earlier overrides stay where they are; move them to `register()` if another package reads that config while it boots.
- `osdd:migration`, `osdd:seeder` and `osdd:config` find the method to edit by parsing the provider, take the provider from the layer's `extra.laravel.providers` when it is declared there, and print the line to add when the layer has no provider or the method is missing. In 2.x they edited the method placed before `register()`, or did nothing.
- Generators write classes under the directory of the layer's root psr-4 entry (the shortest namespace prefix), not always under `src/`.
- `--model` on `osdd:policy`, `osdd:controller`, `osdd:observer` and `osdd:factory` keeps a model of another layer (`--model='Functional\Users\Models\User'`) instead of nesting it under the current layer.
- `osdd:layer`, `osdd:start` and `osdd:provider` keep empty objects such as `"require-dev": {}` in `composer.json`, follow `config.sort-packages`, and write a `../` repository URL for a layer path outside the project.

#### `osdd:command` and `osdd:view` register what they generate

In 2.x, a command generated by `osdd:command` was never registered, and a view generated by `osdd:view` could not be resolved by name. Both now edit the layer provider's `boot()`:

- `osdd:command` adds `$this->commands([\Functional\Orders\Console\Commands\SyncOrders::class]);`.
- `osdd:view` adds `$this->withViews(__DIR__ . '/../../resources/views');` unless the provider already calls `withViews()`. The view then renders as `view('orders::index')`.

If you registered such commands or views by hand (`$this->commands([...])` with an import, or `loadViewsFrom()`), remove the duplicate line the generator adds, or drop your own.
