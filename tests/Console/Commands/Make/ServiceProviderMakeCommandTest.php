<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\LayerServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class ServiceProviderMakeCommandTest extends TestCase
{
    private const string BOOTSTRAP_PROVIDERS = "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n";

    public function test_the_provider_extends_layer_service_provider(): void
    {
        $this->artisan('osdd:provider', ['name' => 'Admin/AuditTrailServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $provider = GeneratedClass::load(
            $this->project()->path('functional/test-layer/src/Providers/Admin/AuditTrailServiceProvider.php'),
            'Functional\TestLayer\Providers\Admin\AuditTrailServiceProvider',
        );

        $this->assertTrue($provider->isSubclassOf(LayerServiceProvider::class));
        $this->assertSame(['register', 'boot'], array_map(
            fn (\ReflectionMethod $method): string => $method->getName(),
            array_values(array_filter($provider->getMethods(), fn (\ReflectionMethod $method): bool => $method->class === $provider->getName())),
        ));
    }

    public function test_a_generator_run_after_it_wires_the_new_provider(): void
    {
        $this->artisan('osdd:provider', ['name' => 'PaymentsServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->doesntExpectOutputToContain('has no service provider')
            ->assertExitCode(0);

        $this->assertStringContainsString(
            "\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');",
            (string) file_get_contents($this->project()->path('functional/test-layer/src/Providers/PaymentsServiceProvider.php')),
        );
    }

    public function test_the_provider_is_declared_in_the_layer_manifest(): void
    {
        $this->artisan('osdd:provider', ['name' => 'ShippingServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertSame(['Functional\TestLayer\Providers\ShippingServiceProvider'], $this->declaredProviders());
    }

    public function test_a_nested_provider_is_declared_under_its_namespace(): void
    {
        $this->artisan('osdd:provider', ['name' => 'Admin/AdminServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertSame(['Functional\TestLayer\Providers\Admin\AdminServiceProvider'], $this->declaredProviders());
    }

    public function test_a_provider_generated_twice_is_declared_once(): void
    {
        $this->artisan('osdd:provider', ['name' => 'ShippingServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);
        $this->artisan('osdd:provider', ['name' => 'ShippingServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertSame(['Functional\TestLayer\Providers\ShippingServiceProvider'], $this->declaredProviders());
    }

    public function test_the_application_bootstrap_providers_are_left_alone(): void
    {
        $this->app['files']->put($this->app->getBootstrapProvidersPath(), self::BOOTSTRAP_PROVIDERS);

        $this->artisan('osdd:provider', ['name' => 'OrdersServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertSame(self::BOOTSTRAP_PROVIDERS, $this->app['files']->get($this->app->getBootstrapProvidersPath()));
        $this->assertSame(['Functional\TestLayer\Providers\OrdersServiceProvider'], $this->declaredProviders());
    }

    /**
     * @return list<string>
     */
    private function declaredProviders(): array
    {
        return $this->app->make(Layers::class)->find('functional/test-layer')->providers;
    }
}
