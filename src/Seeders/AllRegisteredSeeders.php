<?php

namespace Lomkit\Osdd\Seeders;

final class AllRegisteredSeeders implements SeederSelection
{
    #[\Override]
    public function select(array $seeders): array
    {
        return $seeders;
    }

    #[\Override]
    public function nothingSelectedWarning(): string
    {
        return 'No OSDD seeders registered. Make sure your layer service providers call loadSeeders().';
    }
}
