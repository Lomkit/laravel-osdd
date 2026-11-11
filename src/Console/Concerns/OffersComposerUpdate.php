<?php

namespace Lomkit\Osdd\Console\Concerns;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;

trait OffersComposerUpdate
{
    use DetectsInteractiveInput;

    protected function composerUpdateOption(): InputOption
    {
        return new InputOption(
            'composer-update',
            null,
            InputOption::VALUE_NEGATABLE,
            'Run composer update for the new layers (asked when interactive, skipped otherwise)',
        );
    }

    /**
     * Whether the layers are left in a consistent state: false only when the composer update that ran failed.
     */
    protected function offerComposerUpdate(string ...$packages): bool
    {
        if (! $this->wantsComposerUpdate()) {
            $this->components->info('Run <options=bold>composer update '.implode(' ', $packages).'</> to install the new layers.');

            return true;
        }

        $this->components->info('Running composer update...');

        $process = new Process(['composer', 'update', ...$packages], $this->laravel->basePath());
        $process->setTimeout(null);
        $process->run(fn (string $type, string $buffer) => $this->output->write($buffer));

        if (! $process->isSuccessful()) {
            $this->components->error('composer update failed: run composer update '.implode(' ', $packages).' after fixing the error above.');

            return false;
        }

        return true;
    }

    private function wantsComposerUpdate(): bool
    {
        $choice = $this->option('composer-update');

        if (is_bool($choice)) {
            return $choice;
        }

        return $this->canPrompt() && confirm('Run composer update now?', default: true);
    }
}
