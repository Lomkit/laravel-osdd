<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;
use Lomkit\Osdd\Stubs\Stub;

final readonly class ProviderScaffold implements LayerScaffold
{
    public const string KEY = 'provider';

    private const string NAMESPACE_SEGMENT = 'Providers';

    private const string CLASS_SUFFIX = 'ServiceProvider';

    public function __construct(private Stub $stub) {}

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
        $class = $draft->studly().self::CLASS_SUFFIX;
        $namespace = $draft->name->namespace();

        $context->files->write($this->stub, $draft->sourceFile(self::NAMESPACE_SEGMENT.'/'.$class.'.php'), [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ resourceNamespace }}' => LayerResourceNamespace::of($draft->name, $context->layers)->argument(),
        ]);

        $context->files->declareProvider($namespace.'\\'.self::NAMESPACE_SEGMENT.'\\'.$class);
    }
}
