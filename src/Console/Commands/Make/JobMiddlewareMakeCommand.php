<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Lomkit\Osdd\Generation\GeneratesClassesInLayer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:job-middleware')]
final class JobMiddlewareMakeCommand extends \Illuminate\Foundation\Console\JobMiddlewareMakeCommand
{
    use GeneratesClassesInLayer;
}
