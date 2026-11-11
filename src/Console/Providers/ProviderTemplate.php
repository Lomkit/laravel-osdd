<?php

namespace Lomkit\Osdd\Console\Providers;

use Lomkit\Osdd\Console\Providers\Calls\ProviderFile;
use Lomkit\Osdd\Console\Providers\Source\ProviderSource;
use Lomkit\Osdd\Console\Providers\Statements\ProviderStatement;

final class ProviderTemplate
{
    private const string PLACEHOLDER = '/\{\{ (\w+) \}\}/';

    /**
     * Replace each {{ helper }} placeholder of a provider skeleton with the statement calling that helper.
     *
     * @param  list<ProviderStatement>  $statements
     *
     * @throws UnfilledProviderPlaceholderException
     */
    public function fill(string $skeleton, LayerProvider $provider, array $statements): string
    {
        $file = new ProviderFile($provider, ProviderSource::parse($skeleton)->imports);
        $lines = [];

        foreach ($statements as $statement) {
            $call = $statement->call();
            $lines['{{ '.$call->helper.' }}'] = $call->render($file);
        }

        $filled = strtr($skeleton, $lines);

        if (preg_match(self::PLACEHOLDER, $filled, $match) === 1) {
            throw new UnfilledProviderPlaceholderException($match[1]);
        }

        return $filled;
    }
}
