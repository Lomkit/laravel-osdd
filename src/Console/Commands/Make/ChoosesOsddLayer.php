<?php

namespace Lomkit\Osdd\Console\Commands\Make;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Concerns\DetectsInteractiveInput;
use Lomkit\Osdd\Console\MissingLayerOptionException;
use Lomkit\Osdd\Layers\AmbiguousLayerNameException;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\LayerNotFoundException;
use Lomkit\Osdd\Layers\LayersCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\search;

trait ChoosesOsddLayer
{
    use DetectsInteractiveInput;

    protected ?Layer $resolvedLayer = null;

    protected function resolveLayer(): Layer
    {
        if ($this->resolvedLayer !== null) {
            return $this->resolvedLayer;
        }

        $layers = LayersCollection::fromConfig();
        $layerOption = $this->option('layer');

        if (is_string($layerOption) && $layerOption !== '') {
            return $this->resolvedLayer = $layers->findByName($layerOption);
        }

        if (! $this->canPrompt()) {
            throw new MissingLayerOptionException($layers->names());
        }

        $chosen = (string) search(
            label: 'Which layer should this be generated in?',
            options: fn (string $value): array => $layers
                ->toBase()
                ->filter(fn (Layer $l) => str_contains($l->manifest->name(), $value))
                ->mapWithKeys(fn (Layer $l) => [$l->manifest->name() => $l->manifest->name()])
                ->sort()
                ->all(),
        );

        return $this->resolvedLayer = $layers->findByName($chosen);
    }

    #[\Override]
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->resolvedLayer = null;

        try {
            return parent::run($input, $output);
        } catch (LayerNotFoundException|AmbiguousLayerNameException|MissingLayerOptionException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function layerSourcePath(string $class): string
    {
        $layer = $this->resolveLayer();
        $relative = Str::replaceFirst($layer->manifest->rootNamespace(), '', $class);

        return $layer->manifest->srcPath($layer->path).'/'.str_replace('\\', '/', $relative).'.php';
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->resolveLayer();

        parent::interact($input, $output);
    }

    /**
     * Rebuild the definition when the parent generator is declared with a $signature.
     *
     * Illuminate\Console\Command gives the inherited $signature precedence over the $name
     * declared here and skips specifyParameters(), so without this the command would name
     * itself after the parent make:* command and expose no --layer option.
     */
    protected function configureUsingFluentDefinition(): void
    {
        $osddName = $this->name;

        parent::configureUsingFluentDefinition();

        $this->restoreOsddName($osddName);
        $this->addLayerOption();
    }

    /**
     * @return array<mixed>
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [$this->layerOption()]);
    }

    private function restoreOsddName(?string $osddName): void
    {
        if ($osddName === null) {
            return;
        }

        $this->setName($this->name = $osddName);
    }

    private function addLayerOption(): void
    {
        if ($this->getDefinition()->hasOption('layer')) {
            return;
        }

        $this->getDefinition()->addOption($this->layerOption());
    }

    private function layerOption(): InputOption
    {
        return new InputOption('layer', null, InputOption::VALUE_OPTIONAL, 'The layer to generate the file in');
    }
}
