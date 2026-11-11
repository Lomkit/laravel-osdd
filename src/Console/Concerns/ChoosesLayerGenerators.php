<?php

namespace Lomkit\Osdd\Console\Concerns;

use Lomkit\Osdd\Console\Exceptions\UnknownLayerGeneratorException;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\LayerScaffolds;

use function Laravel\Prompts\multiselect;

trait ChoosesLayerGenerators
{
    /**
     * @return list<LayerScaffold>
     *
     * @throws UnknownLayerGeneratorException
     */
    private function chosenScaffolds(LayerScaffolds $scaffolds): array
    {
        /** @var list<string> $option */
        $option = $this->option('generators');

        if ($option !== []) {
            return $scaffolds->parse($option);
        }

        if (! $this->canPrompt()) {
            return $scaffolds->parse($scaffolds->defaultKeys());
        }

        return $scaffolds->parse(multiselect(
            label: 'Which generators should be run?',
            options: $scaffolds->keys(),
            default: $scaffolds->defaultKeys(),
        ));
    }
}
