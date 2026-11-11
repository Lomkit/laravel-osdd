<?php

namespace Lomkit\Osdd\Resources;

final class ResourceNamespaces
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $owners = [];

    /**
     * @throws ResourceNamespaceCollisionException
     */
    public function claim(ResourceKind $kind, string $namespace, string $provider): void
    {
        $owner = $this->owners[$kind->value][$namespace] ?? $provider;

        if ($owner !== $provider) {
            throw new ResourceNamespaceCollisionException($kind, $namespace, $owner, $provider);
        }

        $this->owners[$kind->value][$namespace] = $provider;
    }
}
