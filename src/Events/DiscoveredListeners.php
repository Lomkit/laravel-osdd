<?php

namespace Lomkit\Osdd\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Events\Dispatcher as EventDispatcher;

final readonly class DiscoveredListeners
{
    public function __construct(private Dispatcher $events) {}

    /**
     * Listen to each discovered "Listener@method", unless the same listener method already listens to that event.
     *
     * @param  array<array-key, mixed>  $discovered
     */
    public function listen(array $discovered): void
    {
        foreach ($discovered as $event => $listeners) {
            foreach (is_array($listeners) ? array_unique($listeners, SORT_REGULAR) : [] as $listener) {
                if (is_string($listener) && ! $this->isListening((string) $event, $listener)) {
                    $this->events->listen((string) $event, $listener);
                }
            }
        }
    }

    private function isListening(string $event, string $listener): bool
    {
        $wanted = ListenerReference::of($listener);
        $registered = $this->events instanceof EventDispatcher ? $this->events->getRawListeners()[$event] ?? [] : [];

        foreach (is_array($registered) ? $registered : [] as $candidate) {
            if ($wanted !== null && ListenerReference::of($candidate)?->isSameAs($wanted) === true) {
                return true;
            }
        }

        return false;
    }
}
