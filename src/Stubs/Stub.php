<?php

namespace Lomkit\Osdd\Stubs;

enum Stub: string
{
    case LayerProvider = 'layer/service-provider';
    case WebRoutes = 'layer/routes/web';
    case ApiRoutes = 'layer/routes/api';
    case ConsoleRoutes = 'layer/routes/console';
    case BroadcastChannels = 'layer/routes/channels';
    case OsddConfig = 'layer/osdd-config';
    case OsddProvider = 'layer/osdd-service-provider';
    case UsersProvider = 'start/user-service-provider';
    case UserModel = 'start/user-model';
    case UserFactory = 'start/user-factory';
    case UsersSeeder = 'start/users-seeder';
    case CreateUsersTable = 'start/create-users-table';
    case CreateCacheTable = 'start/create-cache-table';
    case CreateJobsTable = 'start/create-jobs-table';
    case ModelWithFactory = 'make/model.factory';
    case PlainPolicy = 'make/policy';

    public function path(): string
    {
        return __DIR__.'/'.$this->value.'.stub';
    }
}
