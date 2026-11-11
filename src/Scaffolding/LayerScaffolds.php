<?php

namespace Lomkit\Osdd\Scaffolding;

use Lomkit\Osdd\Console\Exceptions\UnknownLayerGeneratorException;
use Lomkit\Osdd\Scaffolding\Scaffolds\ControllerScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\FactoryScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\MigrationScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\ModelScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\PolicyScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\ProviderScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\RoutesScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\SeederScaffold;
use Lomkit\Osdd\Scaffolding\Scaffolds\TestScaffold;
use Lomkit\Osdd\Stubs\Stub;

final class LayerScaffolds
{
    /**
     * @var array<string, LayerScaffold>
     */
    private array $scaffolds = [];

    /**
     * @var list<string>
     */
    private readonly array $defaults;

    /**
     * @param  list<LayerScaffold>  $scaffolds  The scaffolds osdd:layer offers, in the order it lists them
     * @param  list<string>  $defaults  The keys of the scaffolds that run when none is chosen
     *
     * @throws DuplicateScaffoldKeyException
     * @throws UnknownLayerGeneratorException
     */
    public function __construct(array $scaffolds, array $defaults)
    {
        foreach ($scaffolds as $scaffold) {
            if (isset($this->scaffolds[$scaffold->key()])) {
                throw new DuplicateScaffoldKeyException($scaffold->key());
            }

            $this->scaffolds[$scaffold->key()] = $scaffold;
        }

        $this->defaults = array_map(fn (string $key): string => $this->get($key)->key(), $defaults);
    }

    public static function standard(): self
    {
        return new self(
            [
                new MigrationScaffold,
                new ModelScaffold,
                new FactoryScaffold,
                new SeederScaffold,
                new ProviderScaffold(Stub::LayerProvider),
                new TestScaffold,
                new ControllerScaffold,
                new PolicyScaffold,
                new RoutesScaffold,
            ],
            [MigrationScaffold::KEY, ModelScaffold::KEY, FactoryScaffold::KEY, ProviderScaffold::KEY, TestScaffold::KEY],
        );
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(strval(...), array_keys($this->scaffolds));
    }

    /**
     * @return list<string>
     */
    public function defaultKeys(): array
    {
        return $this->defaults;
    }

    /**
     * Parse --generators values, each one a key or a comma-separated list of keys.
     *
     * @param  array<array-key, mixed>  $values
     * @return list<LayerScaffold>
     *
     * @throws UnknownLayerGeneratorException
     */
    public function parse(array $values): array
    {
        $selected = [];

        foreach ($values as $value) {
            foreach (explode(',', is_scalar($value) ? (string) $value : '') as $key) {
                $key = trim($key);

                if ($key !== '' && ! isset($selected[$key])) {
                    $selected[$key] = $this->get($key);
                }
            }
        }

        return array_values($selected);
    }

    /**
     * @throws UnknownLayerGeneratorException
     */
    private function get(string $key): LayerScaffold
    {
        return $this->scaffolds[$key] ?? throw new UnknownLayerGeneratorException($key, $this->keys());
    }
}
