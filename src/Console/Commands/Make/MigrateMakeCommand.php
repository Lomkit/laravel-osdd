<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Generation\GeneratesInLayer;
use Lomkit\Osdd\Layers\LayerFolder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'osdd:migration')]
final class MigrateMakeCommand extends \Illuminate\Database\Console\Migrations\MigrateMakeCommand
{
    use GeneratesInLayer;

    private const array PATH_OPTIONS = ['path', 'realpath'];

    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct($creator, $composer);

        $this->getDefinition()->setOptions(array_filter(
            $this->getDefinition()->getOptions(),
            fn (InputOption $option): bool => ! in_array($option->getName(), self::PATH_OPTIONS, true),
        ));
    }

    #[\Override]
    public function handle(): void
    {
        parent::handle();

        $this->wireInTargetLayer(new LoadMigrations);
    }

    #[\Override]
    protected function getMigrationPath(): string
    {
        return $this->target()->layer->directory->path(LayerFolder::Migrations);
    }
}
