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
