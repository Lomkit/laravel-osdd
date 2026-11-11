<?php

namespace Lomkit\Osdd\Tests\Fakes;

use Illuminate\Filesystem\Filesystem;

final class FakeComposerBinary
{
    public readonly string $path;

    private readonly string $directory;

    public function __construct(int $exitCode = 0, string $output = '')
    {
        $this->directory = sys_get_temp_dir().'/osdd-fake-composer-'.uniqid();
        mkdir($this->directory, 0755, true);
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

    public function delete(): void
    {
        (new Filesystem)->deleteDirectory($this->directory);
    }

    private function callsFile(): string
    {
        return $this->directory.'/calls';
    }
}
