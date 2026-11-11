<?php

namespace Lomkit\Osdd\Tests\Fakes;

use Lomkit\Osdd\Composer\ComposerUpdate;
use Symfony\Component\Console\Output\OutputInterface;

final class FakeComposerUpdate implements ComposerUpdate
{
    /**
     * @var list<list<string>>
     */
    public array $updates = [];

    public function __construct(private readonly bool $succeeds = true) {}

    public static function failing(): self
    {
        return new self(succeeds: false);
    }

    #[\Override]
    public function update(array $packages, OutputInterface $output): bool
    {
        $this->updates[] = $packages;

        return $this->succeeds;
    }
}
