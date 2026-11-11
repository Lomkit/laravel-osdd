<?php

namespace Lomkit\Osdd\Events;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Lomkit\Osdd\Layers\Layers;

final class LayerEventServiceProvider extends EventServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(DiscoveredListeners::class)->listen($this->getEvents());
        });
    }

    #[\Override]
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }

    /**
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function discoverEvents(): array
    {
        return (new LayerListenerDiscovery($this->app->make(Layers::class), $this->app->basePath()))->discover();
    }
}
