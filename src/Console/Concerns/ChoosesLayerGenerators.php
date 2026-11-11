<?php

namespace Lomkit\Osdd\Console\Concerns;

use Lomkit\Osdd\Console\LayerGenerator;
use Lomkit\Osdd\Console\UnknownLayerGeneratorException;

use function Laravel\Prompts\multiselect;

trait ChoosesLayerGenerators
{
    /**
     * @return list<LayerGenerator>
     *
     * @throws UnknownLayerGeneratorException
     */
    private function generators(): array
    {
        /** @var list<string> $option */
        $option = $this->option('generators');

        if ($option !== []) {
            return LayerGenerator::parse($option);
        }

        if (! $this->canPrompt()) {
            return LayerGenerator::defaults();
        }

        return LayerGenerator::parse(multiselect(
            label: 'Which generators should be run?',
            options: LayerGenerator::names(),
            default: array_map(fn (LayerGenerator $generator): string => $generator->value, LayerGenerator::defaults()),
        ));
    }
}
