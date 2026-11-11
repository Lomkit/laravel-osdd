<?php

namespace Lomkit\Osdd\Layers;

enum LayerFolder: string
{
    case Config = 'config';
    case Database = 'database';
    case Factories = 'database/factories';
    case Migrations = 'database/migrations';
    case Routes = 'routes';
    case Seeders = 'database/seeders';
    case Tests = 'tests';
    case Views = 'resources/views';
}
