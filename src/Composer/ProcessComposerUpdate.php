<?php

namespace Lomkit\Osdd\Composer;

use Illuminate\Support\Composer;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final readonly class ProcessComposerUpdate implements ComposerUpdate
{
    public function __construct(
        private Composer $composer,
        private string $workingPath,
        private ?string $composerBinary = null,
    ) {}

    #[\Override]
    public function update(array $packages, OutputInterface $output): bool
    {
        $process = new Process([...$this->composer->findComposer($this->composerBinary), 'update', ...$packages], $this->workingPath);
        $process->setTimeout(null);
        $process->run(fn (string $type, string $buffer) => $output->write($buffer));

        return $process->isSuccessful();
    }
}
