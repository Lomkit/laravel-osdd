<?php

namespace Lomkit\Osdd\Scaffolding;

final class ScaffoldPlan
{
    /**
     * @var array<string, LayerScaffold>
     */
    private array $scaffolds = [];

    /**
     * @var list<LayerScaffold>
     */
    private array $steps = [];

    /**
     * @var list<string>
     */
    private array $visiting = [];

    /**
     * @param  list<LayerScaffold>  $scaffolds
     */
    private function __construct(array $scaffolds)
    {
        foreach ($scaffolds as $scaffold) {
            if (isset($this->scaffolds[$scaffold->key()])) {
                throw new DuplicateScaffoldKeyException($scaffold->key());
            }

            $this->scaffolds[$scaffold->key()] = $scaffold;
        }

        foreach ($this->scaffolds as $key => $scaffold) {
            $this->visit($key);
        }
    }

    /**
     * Order the scaffolds so that each one runs after the scaffolds it depends on, keeping the given order otherwise.
     *
     * @param  list<LayerScaffold>  $scaffolds
     *
     * @throws DuplicateScaffoldKeyException
     * @throws ScaffoldDependencyCycleException
     */
    public static function of(array $scaffolds): self
    {
        return new self($scaffolds);
    }

    /**
     * @return list<LayerScaffold>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    private function visit(string $key): void
    {
        if (in_array($key, $this->visiting, true)) {
            throw new ScaffoldDependencyCycleException([...array_slice($this->visiting, (int) array_search($key, $this->visiting, true)), $key]);
        }

        if (in_array($this->scaffolds[$key], $this->steps, true)) {
            return;
        }

        $this->visiting[] = $key;

        foreach ($this->scaffolds[$key]->dependsOn() as $dependency) {
            if (isset($this->scaffolds[$dependency])) {
                $this->visit($dependency);
            }
        }

        array_pop($this->visiting);
        $this->steps[] = $this->scaffolds[$key];
    }
}
