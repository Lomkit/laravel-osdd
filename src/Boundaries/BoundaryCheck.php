<?php

namespace Lomkit\Osdd\Boundaries;

use Lomkit\Osdd\Filesystem\Path;
use Lomkit\Osdd\Graph\LayerCycles;
use Lomkit\Osdd\Graph\LayerGraph;
use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\Layers\Layers;

final class BoundaryCheck
{
    public function __construct(
        private readonly LayerGraph $graph,
        private readonly LayerReferences $references,
        private readonly BoundaryIgnoreList $ignore,
        private readonly bool $strict,
        private readonly string $basePath,
    ) {}

    public static function of(Layers $layers, BoundaryIgnoreList $ignore, bool $strict, string $basePath): self
    {
        return new self(
            LayerGraph::of($layers),
            new LayerReferences(LayerNamespaces::of($layers), $basePath),
            $ignore,
            $strict,
            $basePath,
        );
    }

    public function run(Layers $selected): BoundaryReport
    {
        $names = $selected->names();
        $undeclared = [];
        $unused = [];

        foreach ($selected as $layer) {
            $references = $this->references->of($layer);
            array_push($undeclared, ...$this->undeclared($layer->name->toString(), $references));
            array_push($unused, ...$this->unused($layer, $references));
        }

        return new BoundaryReport($names, $this->strict, $undeclared, $unused, $this->cycles($names));
    }

    /**
     * @param  list<LayerReference>  $references
     * @return list<UndeclaredDependency>
     */
    private function undeclared(string $layer, array $references): array
    {
        $allowed = $this->allowed($this->graph->requires($layer));
        $allowedInTests = $this->allowed([...$this->graph->requires($layer), ...$this->graph->devRequires($layer)]);
        $undeclared = [];

        foreach ($references as $reference) {
            if (in_array($reference->layer, $reference->fromTests ? $allowedInTests : $allowed, true)
                || $this->ignore->ignoresClass($reference->class)
                || $this->ignore->ignoresPair($layer, $reference->layer)) {
                continue;
            }

            $undeclared[] = new UndeclaredDependency($layer, $reference);
        }

        return $undeclared;
    }

    /**
     * @param  list<LayerReference>  $references
     * @return list<UnusedRequirement>
     */
    private function unused(Layer $layer, array $references): array
    {
        $name = $layer->name->toString();
        $used = array_map(fn (LayerReference $reference): string => $reference->layer, $references);
        $composerFile = Path::relative($this->basePath, $layer->directory->manifestFile());
        $unused = [];

        foreach (RequireSection::cases() as $section) {
            $requirements = $section === RequireSection::Require ? $this->graph->requires($name) : $this->graph->devRequires($name);

            foreach ($requirements as $requirement) {
                if (! in_array($requirement, $used, true) && ! $this->ignore->ignoresPair($name, $requirement)) {
                    $unused[] = new UnusedRequirement($name, $requirement, $section, $composerFile);
                }
            }
        }

        return $unused;
    }

    /**
     * @param  list<string>  $requirements
     * @return list<string>
     */
    private function allowed(array $requirements): array
    {
        return $this->strict ? $requirements : $this->graph->reachableFrom($requirements);
    }

    /**
     * @param  list<string>  $selected
     * @return list<list<string>>
     */
    private function cycles(array $selected): array
    {
        $graph = $this->graph->filterRequires(fn (string $layer, string $requirement): bool => ! $this->ignore->ignoresPair($layer, $requirement));

        return array_values(array_filter(
            LayerCycles::in($graph),
            fn (array $cycle): bool => array_intersect($cycle, $selected) !== [],
        ));
    }
}
