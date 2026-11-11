<?php

namespace Lomkit\Osdd\IdeHelper;

use Illuminate\Contracts\Config\Repository;
use Lomkit\Osdd\Layers\LayersCollection;

final class IdeHelperModelLocations
{
    public function __construct(private readonly Repository $config) {}

    public function __invoke(): void
    {
        if (! $this->config->has('ide-helper.model_locations')) {
            return;
        }

        /** @var list<string> $locations */
        $locations = $this->config->get('ide-helper.model_locations', []);

        foreach (LayersCollection::fromConfig() as $layer) {
            $srcPath = rtrim($layer->manifest->srcPath($layer->path), '/\\');

            if (is_dir($srcPath)) {
                $locations[] = $srcPath;
            }
        }

        $this->config->set('ide-helper.model_locations', array_values(array_unique($locations)));
    }
}
