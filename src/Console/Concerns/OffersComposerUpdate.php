<?php

namespace Lomkit\Osdd\Console\Concerns;

use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;

trait OffersComposerUpdate
{
    protected function offerComposerUpdate(string ...$packages): void
    {
        if (! confirm('Run composer update now?', default: true)) {
            return;
        }

        $this->components->info('Running composer update...');

        $process = new Process(['composer', 'update', ...$packages], $this->laravel->basePath());
        $process->setTimeout(null);
        $process->run(fn (string $type, string $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->components->error('composer update failed. You may need to run it manually.');
        }
    }
}
