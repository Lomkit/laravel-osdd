<?php

namespace Lomkit\Osdd\Tests\Console\Phpunit;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Console\Phpunit\DirectoryPattern;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DirectoryPatternTest extends TestCase
{
    use UsesTemporaryProject;

    private const array FOLDERS = ['src', 'functional/src', 'functional/orders/src', 'functional/orders/tests', 'functional/billing/lib/src', 'technical/a/src', 'technical/b/src'];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::FOLDERS as $folder) {
            (new Filesystem)->ensureDirectoryExists($this->project()->path($folder));
        }

        file_put_contents($this->project()->path('functional/orders/file'), '');
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function patterns(): iterable
    {
        yield 'literal' => ['functional/orders/src', ['functional/orders/src']];
        yield 'dot slash and trailing slash' => [' ./functional/orders/src/ ', ['functional/orders/src']];
        yield 'missing literal' => ['functional/missing', []];
        yield 'file' => ['functional/orders/file', []];
        yield 'star' => ['functional/*/src', ['functional/orders/src']];
        yield 'globstar for zero or more folders' => ['functional/**/src', ['functional/src', 'functional/orders/src', 'functional/billing/lib/src']];
        yield 'leading globstar' => ['**/src', self::sorted(['src', 'functional/src', 'functional/orders/src', 'functional/billing/lib/src', 'technical/a/src', 'technical/b/src'])];
        yield 'question mark' => ['technical/?/src', ['technical/a/src', 'technical/b/src']];
        yield 'character class' => ['technical/[a]/src', ['technical/a/src']];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('patterns')]
    public function test_it_resolves_the_folders_phpunit_reads(string $value, array $expected): void
    {
        $folders = DirectoryPattern::of($value, $this->project()->root)->folders();
        sort($folders);

        $this->assertSame(self::sorted(array_map(fn (string $folder): string => (string) realpath($this->project()->path($folder)), $expected)), $folders);
    }

    public function test_an_absolute_value_ignores_the_phpunit_folder(): void
    {
        $folder = (string) realpath($this->project()->path('functional/orders/src'));

        $this->assertSame([$folder], DirectoryPattern::of($folder, '/elsewhere')->folders());
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
