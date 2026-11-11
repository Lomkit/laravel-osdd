<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Lomkit\Osdd\Composer\InstalledPackages;
use Lomkit\Osdd\Tests\Fixtures\UsesTemporaryProject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InstalledPackagesTest extends TestCase
{
    use UsesTemporaryProject;

    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = $this->project()->path('installed');
        mkdir($this->basePath.'/vendor/composer', 0755, true);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function installedFiles(): array
    {
        return [
            'composer 2' => ['{"packages": [{"name": "functional/orders"}, {"name": "laravel/framework"}, {"version": "1.0"}]}', ['functional/orders', 'laravel/framework']],
            'composer 1' => ['[{"name": "functional/orders"}]', ['functional/orders']],
            'unreadable' => ['{"packages": ', []],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('installedFiles')]
    public function test_it_lists_the_installed_package_names(string $json, array $expected): void
    {
        file_put_contents($this->basePath.'/vendor/composer/installed.json', $json);

        $this->assertSame($expected, InstalledPackages::of(new Filesystem, $this->basePath));
    }

    public function test_nothing_is_installed_before_composer_runs(): void
    {
        $this->assertSame([], InstalledPackages::of(new Filesystem, $this->basePath));
    }
}
