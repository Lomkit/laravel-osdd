<?php

namespace Lomkit\Osdd\Tests\Console\Providers;

use Lomkit\Osdd\Console\Providers\ProviderMethod;
use Lomkit\Osdd\Console\Providers\ProviderMethodEditor;
use Lomkit\Osdd\Console\Providers\ProviderMethodNotFoundException;
use PHPUnit\Framework\TestCase;

class ProviderMethodEditorTest extends TestCase
{
    private const string STATEMENT = '$this->loadMigrationsFrom(__DIR__ . \'/../../database/migrations\');';

    public function test_it_appends_to_boot_when_a_helper_sits_between_boot_and_register(): void
    {
        $source = <<<'PHP'
            <?php

            class OrdersServiceProvider
            {
                public function boot(): void
                {
                    $this->configureRateLimiting();
                }

                protected function configureRateLimiting(): void
                {
                    //
                }

                public function register(): void
                {
                    //
                }
            }
            PHP;

        $expected = <<<'PHP'
            <?php

            class OrdersServiceProvider
            {
                public function boot(): void
                {
                    $this->configureRateLimiting();
                    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
                }

                protected function configureRateLimiting(): void
                {
                    //
                }

                public function register(): void
                {
                    //
                }
            }
            PHP;

        $this->assertSame($expected, $this->append($source, ProviderMethod::Boot));
    }

    public function test_it_finds_boot_after_register_and_docblocks(): void
    {
        $source = <<<'PHP'
            <?php

            class OrdersServiceProvider
            {
                /**
                 * Register services.
                 */
                public function register(): void
                {
                    $this->app->singleton(Foo::class, function () {
                        return new Foo("{$this->name}");
                    });
                }

                /**
                 * Bootstrap services.
                 */
                public function boot(): void
                {
                    if ($this->app->runningInConsole()) {
                        $this->commands([]);
                    }
                }
            }
            PHP;

        $expected = <<<'PHP'
            <?php

            class OrdersServiceProvider
            {
                /**
                 * Register services.
                 */
                public function register(): void
                {
                    $this->app->singleton(Foo::class, function () {
                        return new Foo("{$this->name}");
                    });
                }

                /**
                 * Bootstrap services.
                 */
                public function boot(): void
                {
                    if ($this->app->runningInConsole()) {
                        $this->commands([]);
                    }
                    $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
                }
            }
            PHP;

        $this->assertSame($expected, $this->append($source, ProviderMethod::Boot));
    }

    public function test_it_replaces_the_empty_body_placeholder_of_register(): void
    {
        $source = "<?php\n\nclass P\n{\n\tpublic function boot(): void\n\t{\n\t\t//\n\t}\n\n\tpublic function register(): void\n\t{\n\t\t//\n\t}\n}\n";

        $this->assertSame(
            "<?php\n\nclass P\n{\n\tpublic function boot(): void\n\t{\n\t\t//\n\t}\n\n\tpublic function register(): void\n\t{\n\t\t".self::STATEMENT."\n\t}\n}\n",
            $this->append($source, ProviderMethod::Register),
        );
    }

    public function test_it_ignores_a_closure_or_a_property_named_like_the_method(): void
    {
        $source = <<<'PHP'
            <?php

            class P
            {
                private $boot = 'boot';

                public function register(): void
                {
                    $boot = function boot() {};
                }
            }
            PHP;

        $this->expectException(ProviderMethodNotFoundException::class);

        $this->append($source, ProviderMethod::Boot);
    }

    public function test_it_rejects_an_abstract_method(): void
    {
        $this->expectException(ProviderMethodNotFoundException::class);

        $this->append("<?php\n\nabstract class P\n{\n    abstract public function boot(): void;\n}\n", ProviderMethod::Boot);
    }

    private function append(string $source, ProviderMethod $method): string
    {
        return (new ProviderMethodEditor)->append($source, $method, self::STATEMENT);
    }
}
