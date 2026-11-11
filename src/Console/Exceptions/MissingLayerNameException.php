<?php

namespace Lomkit\Osdd\Console\Exceptions;

use InvalidArgumentException;

class MissingLayerNameException extends InvalidArgumentException implements OsddConsoleException
{
    public function __construct()
    {
        parent::__construct('Pass the layer name (vendor/package) when the command is not interactive: php artisan osdd:layer functional/orders');
    }
}
