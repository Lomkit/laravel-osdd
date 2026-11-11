<?php

namespace Lomkit\Osdd\Console\Commands;

use Illuminate\Console\Command;
use Lomkit\Osdd\Layers\LayerCache;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:clear')]
final class ClearCommand extends Command
{
    protected $signature = 'osdd:clear';

    protected $description = 'Remove the OSDD layer cache file';

    public function handle(LayerCache $cache): int
    {
        $cache->clear();

        $this->components->info('Layer cache cleared successfully.');

        return self::SUCCESS;
    }
}
