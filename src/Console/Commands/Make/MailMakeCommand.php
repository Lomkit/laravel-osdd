<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:mail')]
final class MailMakeCommand extends \Illuminate\Foundation\Console\MailMakeCommand
{
    use GeneratesClassesInLayer;
}
