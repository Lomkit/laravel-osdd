<?php

namespace Lomkit\Osdd\Console\Providers;

use Lomkit\Osdd\Layers\LayerName;

final readonly class ConventionalProvider
{
    private const string NAMESPACE_SEGMENT = 'Providers';

    private const string CLASS_SUFFIX = 'ServiceProvider';

    public string $className;

    public function __construct(LayerName $layer)
    {
        $this->className = $layer->studlyPackage().self::CLASS_SUFFIX;
    }

    public function classIn(string $rootNamespace): string
    {
        return trim($rootNamespace, '\\').'\\'.self::NAMESPACE_SEGMENT.'\\'.$this->className;
    }

    /**
     * The provider file, relative to the layer source directory.
     */
    public function sourceFile(): string
    {
        return self::NAMESPACE_SEGMENT.'/'.$this->className.'.php';
    }
}
