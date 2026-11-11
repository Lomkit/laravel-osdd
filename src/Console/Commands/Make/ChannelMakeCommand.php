<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:channel')]
final class ChannelMakeCommand extends \Illuminate\Foundation\Console\ChannelMakeCommand
{
    use GeneratesClassesInLayer;
}
