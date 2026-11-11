<?php

namespace Lomkit\Osdd\Inventory;

use Lomkit\Osdd\Layers\Layer;
use Lomkit\Osdd\LayerServiceProvider;

final readonly class LayerHealth
{
    private function __construct(
        public bool $required,
        public bool $pathRepository,
        public bool $installed,
        public ?bool $providerExtendsLayerServiceProvider,
        public bool $testSuite,
    ) {}

    public static function of(Layer $layer, RootProject $project): self
    {
        $name = $layer->manifest->name();
        $installed = $project->hasInstalled($name);

        return new self(
            required: $project->requires($name),
            pathRepository: $project->hasPathRepositoryFor($layer->path),
            installed: $installed,
            providerExtendsLayerServiceProvider: self::providersExtendLayerServiceProvider($layer->manifest->providers(), $installed),
            testSuite: $project->hasTestSuite($name),
        );
    }

    /**
     * @return list<string>
     */
    public function problems(): array
    {
        return array_keys(array_filter([
            'not required in composer.json' => ! $this->required,
            'no path repository' => ! $this->pathRepository,
            'not installed' => ! $this->installed,
            'provider does not extend LayerServiceProvider' => $this->providerExtendsLayerServiceProvider === false,
            'no test suite in phpunit.xml' => ! $this->testSuite,
        ]));
    }

    /**
     * @return array{required: bool, path_repository: bool, installed: bool, provider_extends_layer_service_provider: ?bool, test_suite: bool}
     */
    public function toArray(): array
    {
        return [
            'required' => $this->required,
            'path_repository' => $this->pathRepository,
            'installed' => $this->installed,
            'provider_extends_layer_service_provider' => $this->providerExtendsLayerServiceProvider,
            'test_suite' => $this->testSuite,
        ];
    }

    /**
     * Null when the layer is not installed: its provider classes cannot be autoloaded yet.
     *
     * @param  list<string>  $providers
     */
    private static function providersExtendLayerServiceProvider(array $providers, bool $installed): ?bool
    {
        if ($providers === []) {
            return false;
        }

        if (! $installed) {
            return null;
        }

        foreach ($providers as $provider) {
            if (! is_subclass_of($provider, LayerServiceProvider::class)) {
                return false;
            }
        }

        return true;
    }
}
