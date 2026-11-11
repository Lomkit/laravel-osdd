<?php

namespace Lomkit\Osdd\Boundaries;

final readonly class UnusedRequirement
{
    public function __construct(
        public string $layer,
        public string $requirement,
        public RequireSection $section,
        public string $composerFile,
    ) {}

    /**
     * @return array{layer: string, requires: string, section: string}
     */
    public function toArray(): array
    {
        return ['layer' => $this->layer, 'requires' => $this->requirement, 'section' => $this->section->value];
    }
}
