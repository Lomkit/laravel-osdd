<?php

namespace Lomkit\Osdd\Events;

use Illuminate\Foundation\Events\DiscoverEvents;
use Lomkit\Osdd\Layers\Layers;

final readonly class LayerListenerDiscovery
{
    public function __construct(
        private Layers $layers,
        private string $basePath,
    ) {}

    /**
     * The listeners of every layer's src/Listeners by event, with class names resolved through the layer's psr-4.
     *
     * @return array<array-key, mixed>
     */
    public function discover(): array
    {
        $previousGuess = DiscoverEvents::$guessClassNamesUsingCallback;
        $events = [];

        try {
            foreach ($this->layers as $layer) {
                $directory = LayerListenerDirectory::of($layer);

                if ($directory === null || ! $directory->exists()) {
                    continue;
                }

                DiscoverEvents::guessClassNamesUsing($directory->classOf(...));
                $events = array_merge_recursive($events, DiscoverEvents::within($directory->path, $this->basePath));
            }
        } finally {
            DiscoverEvents::$guessClassNamesUsingCallback = $previousGuess;
        }

        return $events;
    }
}
