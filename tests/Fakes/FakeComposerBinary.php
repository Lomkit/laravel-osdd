<?php

namespace Lomkit\Osdd\Tests\Fakes;

final class FakeComposerBinary
{
    public readonly string $path;

    public function __construct(private readonly string $directory, int $exitCode = 0, string $output = '')
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $this->path = $this->directory.'/composer';

        file_put_contents($this->path, '<?php'.PHP_EOL
            .'file_put_contents('.var_export($this->callsFile(), true).', implode(" ", array_slice($argv, 1)).PHP_EOL, FILE_APPEND);'.PHP_EOL
            .'echo '.var_export($output, true).';'.PHP_EOL
            .'exit('.$exitCode.');'.PHP_EOL);
    }

    /**
     * @return list<string>
     */
    public function calls(): array
    {
        return is_file($this->callsFile()) ? file($this->callsFile(), FILE_IGNORE_NEW_LINES) ?: [] : [];
    }

    private function callsFile(): string
    {
        return $this->directory.'/calls';
    }
}
