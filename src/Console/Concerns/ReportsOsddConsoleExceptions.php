<?php

namespace Lomkit\Osdd\Console\Concerns;

use Lomkit\Osdd\Console\Exceptions\OsddConsoleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait ReportsOsddConsoleExceptions
{
    #[\Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::run($input, $output);
        } catch (OsddConsoleException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
