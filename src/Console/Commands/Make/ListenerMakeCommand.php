<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Console\GeneratorCommand;
use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:listener')]
final class ListenerMakeCommand extends \Illuminate\Foundation\Console\ListenerMakeCommand
{
    use GeneratesClassesInLayer;

    private const string EVENTS_NAMESPACE = 'Events\\';

    #[\Override]
    protected function buildClass($name): string
    {
        $class = GeneratorCommand::buildClass($name);
        $event = $this->eventClass();

        if ($event === null) {
            return $class;
        }

        $class = str_replace(['DummyEvent', '{{ event }}'], class_basename($event), $class);

        return str_replace(['DummyFullEvent', '{{ eventNamespace }}'], trim($event, '\\'), $class);
    }

    private function eventClass(): ?string
    {
        $event = (string) $this->option('event');

        if ($event === '') {
            return null;
        }

        if (str_starts_with($event, 'Illuminate') || str_starts_with($event, '\\')) {
            return $event;
        }

        return $this->rootNamespace().self::EVENTS_NAMESPACE.str_replace('/', '\\', $event);
    }
}
