<?php

namespace Lomkit\Osdd\Composer;

use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Layers\LayerName;
use stdClass;

final readonly class LayerComposerManifest
{
    public const string SOURCE_DIRECTORY = 'src';

    private const string TYPE = 'layer';

    private const string VERSION = '1.0.0';

    private const string PACKAGE = 'lomkit/laravel-osdd';

    public const string CONSTRAINT = '*';

    private const string MINIMUM_STABILITY = 'stable';

    /**
     * @param  list<LayerFolder>  $autoloadedFolders
     * @param  list<string>  $requiredLayers
     */
    private function __construct(
        public LayerName $name,
        private string $description,
        private array $autoloadedFolders,
        public array $requiredLayers,
    ) {}

    /**
     * @param  list<LayerFolder>  $autoloadedFolders  Folders mapped to their own namespace next to the source directory
     * @param  list<string>  $requiredLayers
     */
    public static function for(
        LayerName $name,
        string $description = '',
        array $autoloadedFolders = [LayerFolder::Seeders, LayerFolder::Factories],
        array $requiredLayers = [],
    ): self {
        return new self($name, $description, $autoloadedFolders, $requiredLayers);
    }

    public function toObject(): stdClass
    {
        return (object) [
            'name' => $this->name->toString(),
            'description' => $this->description,
            'type' => self::TYPE,
            'version' => self::VERSION,
            'require' => (object) [self::PACKAGE => self::CONSTRAINT],
            'autoload' => (object) ['psr-4' => (object) $this->psr4()],
            'minimum-stability' => self::MINIMUM_STABILITY,
            'prefer-stable' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function psr4(): array
    {
        $namespace = $this->name->namespace().'\\';
        $psr4 = [$namespace => self::SOURCE_DIRECTORY.'/'];

        foreach ($this->autoloadedFolders as $folder) {
            $psr4[$namespace.$folder->namespace().'\\'] = $folder->value.'/';
        }

        return $psr4;
    }
}
