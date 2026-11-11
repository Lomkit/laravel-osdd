<?php

namespace Lomkit\Osdd\Tests\Filesystem;

use Lomkit\Osdd\Filesystem\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function relativePaths(): array
    {
        return [
            'inside' => ['/srv/app', '/srv/app/functional', 'functional'],
            'same' => ['/srv/app', '/srv/app/', ''],
            'sibling' => ['/srv/app', '/srv/layers/functional', '../layers/functional'],
            'dot segments' => ['/srv/app/./', '/srv/app/x/../functional', 'functional'],
            'windows' => ['C:\\www\\app', 'C:\\www\\app\\functional', 'functional'],
            'other drive' => ['C:\\www\\app', 'D:\\layers', 'D:/layers'],
        ];
    }

    #[DataProvider('relativePaths')]
    public function test_it_computes_relative_paths(string $base, string $target, string $expected): void
    {
        $this->assertSame($expected, Path::relative($base, $target));
    }

    public function test_it_normalizes_paths(): void
    {
        $this->assertSame('/srv/app/functional', Path::normalize('/srv/app/./functional/x/../'));
        $this->assertSame('functional/*', Path::normalize('./functional//*'));
        $this->assertSame('../layers', Path::normalize('../layers'));
    }
}
