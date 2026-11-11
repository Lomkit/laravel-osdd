<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CrossLayerModelTest extends TestCase
{
    private const string USER_MODEL = 'Functional\\Users\\Models\\User';

    protected function setUp(): void
    {
        parent::setUp();

        $this->layerFixture()->layer('functional/users', ['autoload' => ['psr-4' => ['Functional\\Users\\' => 'src/']]]);
        $this->app['config']->set('auth.providers.users.model', self::USER_MODEL);
    }

    /**
     * @return array<string, array{string, string, string, string, string, int}>
     */
    public static function generators(): array
    {
        return [
            'policy' => ['osdd:policy', 'MemberPolicy', 'src/Policies/MemberPolicy.php', 'Functional\TestLayer\Policies\MemberPolicy', 'view', 1],
            'controller' => ['osdd:controller', 'MemberController', 'src/Http/Controllers/MemberController.php', 'Functional\TestLayer\Http\Controllers\MemberController', 'show', 0],
            'observer' => ['osdd:observer', 'MemberObserver', 'src/Observers/MemberObserver.php', 'Functional\TestLayer\Observers\MemberObserver', 'created', 0],
        ];
    }

    #[DataProvider('generators')]
    public function test_a_model_of_another_layer_is_typed_as_is(string $command, string $name, string $file, string $class, string $method, int $position): void
    {
        $this->artisan($command, ['name' => $name, '--model' => self::USER_MODEL, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $generated = GeneratedClass::load($this->layerPath($file), $class);

        $this->assertSame(self::USER_MODEL, $this->parameterType($generated->getMethod($method), $position));
    }

    public function test_a_factory_of_a_model_of_another_layer_imports_it_once(): void
    {
        $this->artisan('osdd:factory', ['name' => 'MemberFactory', '--model' => self::USER_MODEL, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $factory = GeneratedClass::load($this->layerPath('database/factories/MemberFactory.php'), 'Functional\TestLayer\Database\Factories\MemberFactory');

        $this->assertSame(1, substr_count((string) file_get_contents((string) $factory->getFileName()), 'use '.self::USER_MODEL.';'));
        $this->assertStringContainsString('@extends Factory<User>', (string) $factory->getDocComment());
    }

    public function test_an_existing_class_is_kept_as_is(): void
    {
        $this->artisan('osdd:policy', ['name' => 'NotificationPolicy', '--model' => 'Illuminate/Notifications/DatabaseNotification', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $policy = GeneratedClass::load($this->layerPath('src/Policies/NotificationPolicy.php'), 'Functional\TestLayer\Policies\NotificationPolicy');

        $this->assertSame('Illuminate\Notifications\DatabaseNotification', $this->parameterType($policy->getMethod('view'), 1));
    }

    public function test_a_short_model_name_is_qualified_under_the_current_layer(): void
    {
        $this->artisan('osdd:policy', ['name' => 'OrderPolicy', '--model' => 'Order', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $policy = GeneratedClass::load($this->layerPath('src/Policies/OrderPolicy.php'), 'Functional\TestLayer\Policies\OrderPolicy');

        $this->assertSame('Functional\TestLayer\Models\Order', $this->parameterType($policy->getMethod('view'), 1));
    }

    private function layerPath(string $file): string
    {
        return $this->project()->path('functional/test-layer/'.$file);
    }

    private function parameterType(\ReflectionMethod $method, int $position): string
    {
        $type = $method->getParameters()[$position]->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : '';
    }
}
