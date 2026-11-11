<?php

namespace Lomkit\Osdd\Console\Exceptions;

use InvalidArgumentException;

class UnknownLayerBucketException extends InvalidArgumentException implements OsddConsoleException
{
    /**
     * @param  list<string>  $buckets
     */
    public function __construct(public readonly string $vendor, public readonly array $buckets)
    {
        parent::__construct("The vendor [{$vendor}] matches no key of osdd.layers.paths (".implode(', ', $buckets).'). Name the layer after one of them, or pass --target-path.');
    }
}
