<?php

namespace Lomkit\Osdd\Console\Providers\Statements;

use Illuminate\Support\Str;
use Lomkit\Osdd\Console\Providers\Calls\LayerPathArgument;
use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\Calls\StringArgument;
use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Layers\LayerFolder;

final readonly class OverrideConfig implements ProviderStatement
{
    private const string LABEL_PREFIX = 'config:';

    private const string EXTENSION = '.php';

    private string $file;

    /**
     * @param  string  $file  The config file name in the layer config folder, such as payments.php
     */
    public function __construct(string $file)
    {
        $this->file = Str::finish($file, self::EXTENSION);
    }

    #[\Override]
    public function method(): ProviderMethod
    {
        return ProviderMethod::Register;
    }

    #[\Override]
    public function call(): ProviderCall
    {
        return new ProviderCall('overrideConfigFrom', PresenceRule::SameCall, LayerPathArgument::file(LayerFolder::Config, $this->file), new StringArgument($this->key()));
    }

    #[\Override]
    public function label(): string
    {
        return self::LABEL_PREFIX.$this->key();
    }

    private function key(): string
    {
        return Str::before($this->file, self::EXTENSION);
    }
}
