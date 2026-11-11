<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Layers\Layers;
use Lomkit\Osdd\Tests\TestCase;

class ServiceProviderMakeCommandTest extends TestCase
{
    private const string BOOTSTRAP_PROVIDERS = "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n";

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
