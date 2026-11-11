<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Layers\LayerFolder;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;
use Lomkit\Osdd\Stubs\Stub;

final readonly class RoutesScaffold implements LayerScaffold
{
    public const string KEY = 'routes';

    private const array FILES = [
        'web.php' => Stub::WebRoutes,
        'api.php' => Stub::ApiRoutes,
        'console.php' => Stub::ConsoleRoutes,
        'channels.php' => Stub::BroadcastChannels,
    ];

    #[\Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[\Override]
    public function dependsOn(): array
    {
        return [];
    }

    #[\Override]
    public function scaffold(LayerDraft $draft, ScaffoldContext $context): void
    {
        foreach (self::FILES as $file => $stub) {
            $context->files->write($stub, LayerFolder::Routes->value.'/'.$file);
        }
    }
}
