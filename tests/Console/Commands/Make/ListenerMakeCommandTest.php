<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ListenerMakeCommandTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function events(): array
    {
        return [
            'event of the layer' => ['NotifyWarehouse', 'StockReserved', 'Functional\TestLayer\Events\StockReserved'],
            'nested event of the layer' => ['NotifyCarrier', 'Shipping/ParcelDispatched', 'Functional\TestLayer\Events\Shipping\ParcelDispatched'],
            'framework event' => ['AuditLogin', 'Illuminate\Auth\Events\Login', 'Illuminate\Auth\Events\Login'],
        ];
    }

    #[DataProvider('events')]
    public function test_the_listener_handles_the_event_it_was_given(string $listener, string $event, string $eventClass): void
    {
        $this->artisan('osdd:listener', ['name' => $listener, '--event' => $event, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $handle = GeneratedClass::load(
            $this->project()->path("functional/test-layer/src/Listeners/{$listener}.php"),
            'Functional\TestLayer\Listeners\\'.$listener,
        )->getMethod('handle');
        $type = $handle->getParameters()[0]->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame($eventClass, $type->getName());
    }
}
