<?php

namespace Lomkit\Osdd\Console\Providers\Wiring;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Providers\Calls\ProviderFile;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderMethodEditor;
use Lomkit\Osdd\Console\Providers\ProviderMethodNotFoundException;
use Lomkit\Osdd\Console\Providers\Source\ProviderSource;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;
use Lomkit\Osdd\Layers\Layer;

final readonly class ProviderWiring
{
    public function __construct(
        private Filesystem $files,
        private ProviderMethodEditor $editor,
    ) {}

    /**
     * Append the statement to its method of the layer provider extending LayerServiceProvider, unless the provider
     * already makes it.
     */
    public function add(Layer $layer, ProviderStatement $statement): WiringOutcome
    {
        $provider = LayerProvider::of($layer);
        $call = $statement->call();

        if ($provider === null) {
            return new ProviderMissing($layer->name, $statement->method(), $call->render(ProviderFile::withoutImports(LayerProvider::conventional($layer))));
        }

        $source = ProviderSource::parse($this->files->get($provider->path));

        if ($source->contains($call, $provider)) {
            return new StatementAlreadyPresent;
        }

        $line = $call->render(new ProviderFile($provider, $source->imports));

        try {
            $this->files->put($provider->path, $this->editor->append($source->code, $statement->method(), $line));
        } catch (ProviderMethodNotFoundException) {
            return new ProviderMethodMissing($provider->path, $statement->method(), $line);
        }

        return new StatementAdded($statement->label(), $provider->path);
    }
}
