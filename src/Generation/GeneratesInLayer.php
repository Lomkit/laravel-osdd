<?php

namespace Lomkit\Osdd\Generation;

use Lomkit\Osdd\Console\Concerns\DetectsInteractiveInput;
use Lomkit\Osdd\Console\Concerns\ReportsOsddConsoleExceptions;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;
use Lomkit\Osdd\Console\Providers\Wiring\ProviderWiring;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @phpstan-require-extends \Illuminate\Console\Command
 */
trait GeneratesInLayer
{
    use DetectsInteractiveInput;
    use ReportsOsddConsoleExceptions;

    private const string LAYER_OPTION = 'layer';

    private ?GenerationTarget $generationTarget = null;

    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->setName($this->name = CommandName::of(static::class));
        $this->aliases = [];
        $this->getDefinition()->addOption(new InputOption(self::LAYER_OPTION, null, InputOption::VALUE_OPTIONAL, 'The layer to generate the file in'));
    }

    #[\Override]
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->generationTarget = $this->laravel->make(LayerTargetResolver::class)
            ->resolve($this->requestedLayer($input), $this->canPrompt());
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::execute($input, $output);
        } finally {
            $this->generationTarget = null;
        }
    }

    protected function target(): GenerationTarget
    {
        return $this->generationTarget ?? throw new GenerationTargetNotResolvedException(static::class);
    }

    /**
     * @param  class-string<Command>  $generator
     * @param  array<string, mixed>  $arguments
     */
    protected function generateInTargetLayer(string $generator, array $arguments): int
    {
        return $this->call($generator, [...$arguments, '--'.self::LAYER_OPTION => $this->target()->layer->name->toString()]);
    }

    protected function wireInTargetLayer(ProviderStatement $statement): void
    {
        $this->laravel->make(ProviderWiring::class)->add($this->target()->layer, $statement)->report($this->output);
    }

    private function requestedLayer(InputInterface $input): ?string
    {
        $layer = $input->getOption(self::LAYER_OPTION);

        return is_string($layer) && $layer !== '' ? $layer : null;
    }
}
