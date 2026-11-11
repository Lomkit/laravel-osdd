<?php

namespace Lomkit\Osdd\Console\Providers\Calls;

final readonly class ProviderCall
{
    /**
     * @var list<CallArgument>
     */
    public array $arguments;

    public function __construct(
        public string $helper,
        public PresenceRule $presence,
        CallArgument ...$arguments,
    ) {
        $this->arguments = array_values($arguments);
    }

    public function render(ProviderFile $file): string
    {
        $arguments = array_map(fn (CallArgument $argument): string => $argument->render($file), $this->arguments);

        return '$this->'.$this->helper.'('.implode(', ', $arguments).');';
    }
}
