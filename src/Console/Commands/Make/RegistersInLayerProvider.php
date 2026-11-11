<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Console\Providers\ProviderMethodEditor;
use Lomkit\Osdd\Console\Providers\ProviderMethodNotFoundException;

trait RegistersInLayerProvider
{
    /**
     * Append a statement to a method of the layer's service provider, once.
     *
     * Warns with the line to add by hand when the layer has no provider or the
     * method cannot be found.
     *
     * @param  Closure(LayerProvider): string  $statement
     */
    protected function registerInLayerProvider(ProviderMethod $method, Closure $statement, string $label, ?string $existingCall = null): void
    {
        $layer = $this->target()->layer;
        $provider = LayerProvider::of($layer);
        $line = $statement($provider);

        if (! $provider->exists()) {
            $this->components->warn("Layer [{$layer->name->toString()}] has no service provider. Add this line to the {$method->value}() method of its provider:");
            $this->line($line);

            return;
        }

        $files = app(Filesystem::class);
        $source = $files->get($provider->path);

        if (str_contains($source, $line)) {
            return;
        }

        if ($existingCall !== null && str_contains($source, $existingCall)) {
            return;
        }

        try {
            $files->put($provider->path, (new ProviderMethodEditor)->append($source, $method, $line));
        } catch (ProviderMethodNotFoundException) {
            $this->components->warn("No {$method->value}() method found in [{$provider->path}]. Add this line to it:");
            $this->line($line);

            return;
        }

        $this->components->info("Registered [{$label}] in [{$provider->path}].");
    }
}
