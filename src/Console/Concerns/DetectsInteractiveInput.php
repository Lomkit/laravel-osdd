<?php

namespace Lomkit\Osdd\Console\Concerns;

trait DetectsInteractiveInput
{
    /**
     * Whether prompts can be shown: not under --no-interaction, and on a terminal
     * (prompts are faked under unit tests, as Laravel does).
     */
    protected function canPrompt(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        return (defined('STDIN') && stream_isatty(STDIN)) || $this->laravel->runningUnitTests();
    }
}
