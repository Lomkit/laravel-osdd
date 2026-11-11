<?php

namespace Lomkit\Osdd\Tests\Scaffolding\Fixtures;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:unregistered')]
final class UnregisteredCommand extends Command {}
