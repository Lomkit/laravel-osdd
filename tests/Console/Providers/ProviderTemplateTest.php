<?php

namespace Lomkit\Osdd\Tests\Console\Providers;

use Lomkit\Osdd\Console\Providers\LayerProvider;
use Lomkit\Osdd\Console\Providers\ProviderTemplate;
use Lomkit\Osdd\Console\Providers\Statements\LoadMigrations;
use Lomkit\Osdd\Console\Providers\Statements\LoadSeeders;
use Lomkit\Osdd\Console\Providers\Statements\OverrideConfig;
use Lomkit\Osdd\Console\Providers\UnfilledProviderPlaceholderException;
use Lomkit\Osdd\Layers\LayerDirectory;
use PHPUnit\Framework\TestCase;

class ProviderTemplateTest extends TestCase
{
    public function test_each_placeholder_takes_the_statement_calling_its_helper(): void
    {
        $skeleton = <<<'PHP'
            <?php

            namespace Functional\Users\Providers;

            use Functional\Users\Database\Seeders\UsersSeeder;

            class UsersServiceProvider
            {
                public function boot(): void
                {
                    {{ loadMigrationsFrom }}
                    {{ loadSeeders }}
                }
            }
            PHP;

        $filled = (new ProviderTemplate)->fill($skeleton, $this->provider(), [
            new LoadMigrations,
            new LoadSeeders('Functional\Users\Database\Seeders\UsersSeeder'),
            new OverrideConfig('users.php'),
        ]);

        $this->assertSame(str_replace(
            ['{{ loadMigrationsFrom }}', '{{ loadSeeders }}'],
            ["\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');", '$this->loadSeeders([UsersSeeder::class]);'],
            $skeleton,
        ), $filled);
    }

    public function test_a_placeholder_without_statement_fails(): void
    {
        $this->expectException(UnfilledProviderPlaceholderException::class);
        $this->expectExceptionMessage('No statement fills the [withViews] placeholder of the provider stub.');

        (new ProviderTemplate)->fill("<?php\n\nclass UsersServiceProvider\n{\n    {{ withViews }}\n}\n", $this->provider(), [new LoadMigrations]);
    }

    private function provider(): LayerProvider
    {
        $directory = new LayerDirectory('/project/functional/users');

        return LayerProvider::at($directory, $directory->pathTo('src/Providers/UsersServiceProvider.php'));
    }
}
