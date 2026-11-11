<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;

class CrossLayerModelTest extends TestCase
{
    private const string USER_MODEL = 'Functional\\Users\\Models\\User';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/users'));
        $this->app['files']->put($this->app->basePath('functional/users/composer.json'), json_encode([
            'name' => 'functional/users',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\Users\\' => 'src/']],
        ]));

        $this->app['config']->set('auth.providers.users.model', self::USER_MODEL);
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/users'));

        foreach (['src/Policies', 'src/Http', 'src/Observers', 'database'] as $directory) {
            $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/'.$directory));
        }

        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/database/migrations'));
        $this->app['files']->put($this->app->basePath('functional/test-layer/database/migrations/.gitkeep'), '');
        $this->app['files']->ensureDirectoryExists($this->app->basePath('functional/test-layer/src/Http'));

        parent::tearDown();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function generators(): array
    {
        return [
            'policy' => ['osdd:policy', 'UserPolicy', 'src/Policies/UserPolicy.php'],
            'controller' => ['osdd:controller', 'UserController', 'src/Http/Controllers/UserController.php'],
            'observer' => ['osdd:observer', 'UserObserver', 'src/Observers/UserObserver.php'],
            'factory' => ['osdd:factory', 'UserFactory', 'database/factories/UserFactory.php'],
        ];
    }

    #[DataProvider('generators')]
    public function test_a_model_of_another_layer_is_kept_as_is(string $command, string $name, string $file): void
    {
        $this->artisan($command, ['name' => $name, '--model' => self::USER_MODEL, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $path = $this->app->basePath('functional/test-layer/'.$file);
        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('use '.self::USER_MODEL.';', $contents);
        $this->assertStringNotContainsString('Functional\\TestLayer\\Models\\Functional', $contents);
        $this->assertSame(1, substr_count($contents, 'use '.self::USER_MODEL.';'));
        $this->assertCompiles($path);
    }

    public function test_an_existing_class_is_kept_as_is(): void
    {
        $this->artisan('osdd:policy', ['name' => 'NotificationPolicy', '--model' => 'Illuminate/Notifications/DatabaseNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertStringContainsString(
            'use Illuminate\\Notifications\\DatabaseNotification;',
            (string) file_get_contents($this->app->basePath('functional/test-layer/src/Policies/NotificationPolicy.php')),
        );
    }

    public function test_a_short_model_name_is_qualified_under_the_current_layer(): void
    {
        $this->artisan('osdd:policy', ['name' => 'OrderPolicy', '--model' => 'Order', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertStringContainsString(
            'use Functional\\TestLayer\\Models\\Order;',
            (string) file_get_contents($this->app->basePath('functional/test-layer/src/Policies/OrderPolicy.php')),
        );
    }

    private function assertCompiles(string $path): void
    {
        $lint = new Process([PHP_BINARY, '-l', $path]);
        $lint->run();

        $this->assertTrue($lint->isSuccessful(), $lint->getOutput().$lint->getErrorOutput());
    }
}
