<?php

namespace Lomkit\Osdd\Console;

use Illuminate\Contracts\Container\Container;
use Lomkit\Osdd\Console\Commands\CacheCommand;
use Lomkit\Osdd\Console\Commands\CheckCommand;
use Lomkit\Osdd\Console\Commands\ClearCommand;
use Lomkit\Osdd\Console\Commands\GraphCommand;
use Lomkit\Osdd\Console\Commands\LayerCommand;
use Lomkit\Osdd\Console\Commands\ListCommand;
use Lomkit\Osdd\Console\Commands\Make\CacheTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\CastMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ChannelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ClassMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ComponentMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConfigMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ConsoleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ControllerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EnumMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\EventMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ExceptionMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\FactoryMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\InterfaceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\JobMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\JobMiddlewareMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ListenerMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\LivewireMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MailMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MiddlewareMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\MigrateMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ModelMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\NotificationMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\NotificationsTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ObserverMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\PolicyMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueBatchesTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueFailedTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\QueueTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RequestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ResourceMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\RuleMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ScopeMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SeederMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ServiceProviderMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\SessionTableMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TestMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\TraitMakeCommand;
use Lomkit\Osdd\Console\Commands\Make\ViewMakeCommand;
use Lomkit\Osdd\Console\Commands\PhpunitCommand;
use Lomkit\Osdd\Console\Commands\RemoveCommand;
use Lomkit\Osdd\Console\Commands\SeedCommand;
use Lomkit\Osdd\Console\Commands\StartCommand;
use Lomkit\Osdd\Livewire\InstalledLivewire;

final class OsddCommands
{
    private const array COMMANDS = [
        LayerCommand::class,
        RemoveCommand::class,
        CacheCommand::class,
        ClearCommand::class,
        ListCommand::class,
        CheckCommand::class,
        GraphCommand::class,
        StartCommand::class,
        PhpunitCommand::class,
        SeedCommand::class,
        CacheTableMakeCommand::class,
        CastMakeCommand::class,
        ChannelMakeCommand::class,
        ClassMakeCommand::class,
        ComponentMakeCommand::class,
        ConfigMakeCommand::class,
        ConsoleMakeCommand::class,
        ControllerMakeCommand::class,
        EnumMakeCommand::class,
        EventMakeCommand::class,
        ExceptionMakeCommand::class,
        FactoryMakeCommand::class,
        InterfaceMakeCommand::class,
        JobMakeCommand::class,
        JobMiddlewareMakeCommand::class,
        ListenerMakeCommand::class,
        MailMakeCommand::class,
        MiddlewareMakeCommand::class,
        MigrateMakeCommand::class,
        ModelMakeCommand::class,
        NotificationMakeCommand::class,
        NotificationsTableMakeCommand::class,
        ObserverMakeCommand::class,
        PolicyMakeCommand::class,
        QueueBatchesTableMakeCommand::class,
        QueueFailedTableMakeCommand::class,
        QueueTableMakeCommand::class,
        RequestMakeCommand::class,
        ResourceMakeCommand::class,
        RuleMakeCommand::class,
        ScopeMakeCommand::class,
        SeederMakeCommand::class,
        ServiceProviderMakeCommand::class,
        SessionTableMakeCommand::class,
        TestMakeCommand::class,
        TraitMakeCommand::class,
        ViewMakeCommand::class,
    ];

    /**
     * @return list<class-string>
     */
    public static function available(Container $container): array
    {
        return InstalledLivewire::runsIn($container)
            ? [...self::COMMANDS, LivewireMakeCommand::class]
            : self::COMMANDS;
    }
}
