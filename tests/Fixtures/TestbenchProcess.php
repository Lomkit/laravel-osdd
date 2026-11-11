<?php

namespace Lomkit\Osdd\Tests\Fixtures;

use Symfony\Component\Process\Process;

final class TestbenchProcess
{
    /**
     * Run vendor/bin/testbench without a terminal against the given project.
     *
     * @param  list<string>  $arguments
     * @param  array<string, string>  $environment
     */
    public static function run(TemporaryProject $project, array $arguments, array $environment = []): Process
    {
        $process = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', 'vendor/bin/testbench', ...$arguments],
            dirname(__DIR__, 2),
            ['APP_BASE_PATH' => $project->root, ...$environment],
        );
        $process->setInput('');
        $process->run();

        return $process;
    }
}
