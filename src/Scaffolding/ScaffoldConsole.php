<?php

namespace Lomkit\Osdd\Scaffolding;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Console\Kernel;
use Lomkit\Osdd\Generation\CommandName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;

final class ScaffoldConsole
{
    private readonly Factory $components;

    public function __construct(
        private readonly Kernel $artisan,
        private readonly OutputStyle $output,
        private readonly bool $interactive,
    ) {
        $this->components = new Factory($output);
    }

    /**
     * Run a registered command on this output; --force is passed only to a command that has the option.
     *
     * @param  class-string<Command>  $command
     * @param  array<string, string|bool|null>  $arguments
     */
    public function call(string $command, array $arguments, bool $force = false): void
    {
        $name = CommandName::of($command);
        $registered = $this->artisan->all()[$name] ?? null;

        if (! $registered instanceof Command) {
            throw new CommandNotFoundException("Command [{$name}] is not registered.");
        }

        $input = new ArrayInput(array_filter(
            ['command' => $name, ...$arguments, '--force' => $force && $registered->getDefinition()->hasOption('force')],
            fn (string|bool|null $value): bool => $value !== null && $value !== false,
        ));
        $input->setInteractive($this->interactive);

        $registered->run($input, $this->output);
    }

    public function info(string $message): void
    {
        $this->components->info($message);
    }

    public function warn(string $message): void
    {
        $this->components->warn($message);
    }
}
