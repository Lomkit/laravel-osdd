<?php

namespace Lomkit\Osdd\Tests\Stubs;

use Lomkit\Osdd\Stubs\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class StubTest extends TestCase
{
    public function test_every_stub_file_has_a_case_and_every_case_a_file(): void
    {
        $files = [];

        foreach ((new Finder)->files()->name('*.stub')->in(dirname(__DIR__, 2).'/src/Stubs') as $file) {
            $files[] = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -strlen('.stub')));
        }

        $cases = array_map(fn (Stub $stub): string => $stub->value, Stub::cases());
        sort($files);
        sort($cases);

        $this->assertSame($files, $cases);

        foreach (Stub::cases() as $stub) {
            $this->assertFileExists($stub->path());
        }
    }
}
