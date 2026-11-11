<?php

namespace Lomkit\Osdd\Console\Concerns;

use Lomkit\Osdd\Composer\ComposerUpdate;
use Lomkit\Osdd\Composer\ComposerUpdateReason;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\confirm;

trait OffersComposerUpdate
{
    use DetectsInteractiveInput;

    protected function composerUpdateOption(ComposerUpdateReason $reason): InputOption
    {
        return new InputOption('composer-update', null, InputOption::VALUE_NEGATABLE, $reason->optionDescription());
    }

    /**
     * Whether the layers are left in a consistent state: false only when the composer update that ran failed.
     */
    protected function offerComposerUpdate(ComposerUpdateReason $reason, string ...$packages): bool
    {
        $command = 'composer update '.implode(' ', $packages);

        if (! $this->wantsComposerUpdate()) {
            $this->components->info($reason->skippedHint($command));

            return true;
        }

        $this->components->info('Running composer update...');

        if (! $this->laravel->make(ComposerUpdate::class)->update(array_values($packages), $this->output)) {
            $this->components->error("composer update failed: run {$command} after fixing the error above.");

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
