<?php

namespace Lomkit\Osdd\Scaffolding\Scaffolds;

use Lomkit\Osdd\Console\Providers\ConventionalProvider;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\LayerResourceNamespace;
use Lomkit\Osdd\Console\Providers\ProviderTemplate;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;
use Lomkit\Osdd\Console\Providers\Statements\WithTranslations;
use Lomkit\Osdd\Console\Providers\Statements\WithViews;
use Lomkit\Osdd\Scaffolding\LayerDraft;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldContext;
use Lomkit\Osdd\Stubs\Stub;

final readonly class ProviderScaffold implements LayerScaffold
{
    public const string KEY = 'provider';

    /**
     * @param  list<ProviderStatement>  $statements  The statements the stub places besides the layer migrations, views and translations
     */
    public function __construct(
        private Stub $stub,
        private array $statements = [],
    ) {}

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
        $conventional = new ConventionalProvider($draft->name);
        $file = $draft->sourceFile($conventional->sourceFile());
        $resourceNamespace = LayerResourceNamespace::of($draft->name, $context->layers);

        $skeleton = $context->files->render($this->stub, [
            '{{ namespace }}' => $draft->name->namespace(),
            '{{ class }}' => $conventional->className,
        ]);

        $context->files->put($file, (new ProviderTemplate)->fill(
            $skeleton,
            LayerProvider::at($draft->directory, $draft->directory->pathTo($file)),
            [new LoadMigrations, new WithViews($resourceNamespace), new WithTranslations($resourceNamespace), ...$this->statements],
        ));

        $context->files->declareProvider($conventional->classIn($draft->name->namespace()));
    }
}
