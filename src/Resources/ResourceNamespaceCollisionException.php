<?php

namespace Lomkit\Osdd\Resources;

use RuntimeException;

class ResourceNamespaceCollisionException extends RuntimeException
{
    public function __construct(
        public readonly ResourceKind $kind,
        public readonly string $namespace,
        public readonly string $firstProvider,
        public readonly string $secondProvider,
    ) {
        parent::__construct(
            "The {$kind->value} {$kind->identifier()} [{$namespace}] is registered by both [{$firstProvider}] and [{$secondProvider}]. "
            ."Pass another {$kind->identifier()} as the second argument of {$kind->helper()}() in one of them.",
        );
    }
}
