# Upgrade Guide

## Upgrading to 3.0 from 2.x

Version 3.0 moves the package from Xefi to Lomkit. The package is now `lomkit/laravel-osdd` and its namespace is `Lomkit\Osdd`. Its behaviour does not change: the commands, the configuration and the layer structure stay the same.

`xefi/laravel-osdd` stays installable at 2.x but receives no new release.

### Requirements

Unchanged: PHP 8.3 or later, Laravel 12 or 13.

### Package name

Every layer requires the package in its own `composer.json`. Replace the requirement in each layer of `functional/` and `technical/`:

```diff
 "require": {
-    "xefi/laravel-osdd": "*"
+    "lomkit/laravel-osdd": "*"
 }
```

Then replace it in the root `composer.json` and update:

```diff
 "require": {
-    "xefi/laravel-osdd": "^2.0"
+    "lomkit/laravel-osdd": "^3.0"
 }
```

```bash
composer update
```

### Namespace

Replace `Xefi\LaravelOSDD\` with `Lomkit\Osdd\` in your layers. Every layer service provider extends the package's base class:

```diff
-use Xefi\LaravelOSDD\LayerServiceProvider;
+use Lomkit\Osdd\LayerServiceProvider;
```

On Linux, one command covers every layer:

```bash
grep -rl 'Xefi\\LaravelOSDD' functional technical | xargs sed -i 's/Xefi\\LaravelOSDD/Lomkit\\Osdd/g'
```

### Service provider

The package's service provider is renamed from `Xefi\LaravelOSDD\LaravelOSDDServiceProvider` to `Lomkit\Osdd\OsddServiceProvider`. Laravel discovers it, so only an application that registers it by hand, or excludes it from discovery in `dont-discover`, changes the class name.

### Laravel Boost

The Boost skill now targets `lomkit/laravel-osdd`. Run `php artisan boost:install` again to refresh the copy in your project.
