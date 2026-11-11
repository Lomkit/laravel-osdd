<?php

namespace Lomkit\Osdd\Tests\Events;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class FixtureEventsProvider extends ServiceProvider
{
    public static ?bool $discoverInRegister = null;

    /**
     * @var list<array{string, string|array{string, string}}>
     */
    public static array $listenedByHand = [];

    public function register(): void
    {
        if (self::$discoverInRegister !== null) {
            $this->app['config']->set('osdd.events.discover', self::$discoverInRegister);
        }
    }

    public function boot(): void
    {
        foreach (self::$listenedByHand as [$event, $listener]) {
            Event::listen($event, $listener);
        }
    }

    public static function reset(): void
    {
        self::$discoverInRegister = null;
        self::$listenedByHand = [];
    }
}
