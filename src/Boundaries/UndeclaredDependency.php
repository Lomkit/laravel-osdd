<?php

namespace Lomkit\Osdd\Boundaries;

final readonly class UndeclaredDependency
{
    public function __construct(
        public string $layer,
        public LayerReference $reference,
    ) {}

    /**
     * @return array{layer: string, uses: string, class: string, file: string, line: int}
     */
    public function toArray(): array
    {
        return [
            'layer' => $this->layer,
            'uses' => $this->reference->layer,
            'class' => $this->reference->class,
            'file' => $this->reference->file,
            'line' => $this->reference->line,
        ];
    }
}
