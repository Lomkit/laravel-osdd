<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ViewMakeCommandTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function views(): array
    {
        return [
            'view' => ['index', 'resources/views/index.blade.php'],
            'nested view' => ['orders/index', 'resources/views/orders/index.blade.php'],
        ];
    }

    #[DataProvider('views')]
    public function test_the_view_is_written_in_the_layer(string $name, string $file): void
    {
        $this->artisan('osdd:view', ['name' => $name, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/'.$file));
        $this->assertDirectoryDoesNotExist($this->project()->path('resources/views/orders'));
        $this->assertFileDoesNotExist($this->project()->path('resources/views/index.blade.php'));
    }

    public function test_it_asks_for_the_layer_when_none_is_given(): void
    {
        $this->artisan('osdd:view', ['name' => 'index'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/resources/views/index.blade.php'));
    }
}
