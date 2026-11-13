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
     * The listeners of every layer's src/Listeners by event, with class names resolved through the layer's psr-4,
     * ordered by layer name, then by "Class@method" in byte order.
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
                $events = array_merge_recursive($events, $this->sortedByListener(DiscoverEvents::within($directory->path, $this->basePath)));
            }
        } finally {
            DiscoverEvents::$guessClassNamesUsingCallback = $previousGuess;
        }

        return $events;
    }

    /**
     * @param  array<array-key, mixed>  $events
     * @return array<array-key, mixed>
     */
    private function sortedByListener(array $events): array
    {
        return array_map(function (mixed $listeners): mixed {
            if (! is_array($listeners)) {
                return $listeners;
            }

            $listeners = array_filter($listeners, is_string(...));
            sort($listeners, SORT_STRING);

            return $listeners;
        }, $events);
    }
}
