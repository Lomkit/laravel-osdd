<?php

namespace Lomkit\Osdd\Layers;

use Illuminate\Support\Str;

final class LayerName
{
    private const string COMPOSER_PACKAGE_NAME = '{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}D';

    private const string PHP_NAMESPACE_SEGMENT = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    private function __construct(
        public readonly string $vendor,
        public readonly string $package,
    ) {}

    /**
     * @throws InvalidLayerNameException
     */
    public static function fromString(string $name): self
    {
        if (preg_match(self::COMPOSER_PACKAGE_NAME, $name) !== 1) {
            throw InvalidLayerNameException::notAComposerName($name);
        }

        [$vendor, $package] = explode('/', $name);
        $layerName = new self($vendor, $package);

        foreach (explode('\\', $layerName->namespace()) as $segment) {
            if (preg_match(self::PHP_NAMESPACE_SEGMENT, $segment) !== 1) {
                throw InvalidLayerNameException::notANamespace($name, $layerName->namespace());
            }
        }

        return $layerName;
    }

    public static function validationError(string $name): ?string
    {
        try {
            self::fromString($name);
        } catch (InvalidLayerNameException $exception) {
            return $exception->getMessage();
        }

        return null;
    }

    public function namespace(): string
    {
        return $this->studly($this->vendor).'\\'.$this->studly($this->package);
    }

    public function studlyPackage(): string
    {
        return $this->studly($this->package);
    }

    public function toString(): string
    {
        return $this->vendor.'/'.$this->package;
    }

    private function studly(string $part): string
    {
        return Str::studly(str_replace('.', '-', $part));
    }
}
