<?php

namespace Lomkit\Osdd\Composer;

use Symfony\Component\Console\Output\OutputInterface;

interface ComposerUpdate
{
    /**
     * @param  list<string>  $packages
     */
    public function update(array $packages, OutputInterface $output): bool;
}
