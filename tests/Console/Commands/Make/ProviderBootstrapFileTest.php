<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\TestCase;

class ProviderBootstrapFileTest extends TestCase
{
    private const string BOOTSTRAP_PROVIDERS = "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n";

    private string $originalComposerJson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalComposerJson = $this->app['files']->get($this->app->basePath('functional/test-layer/composer.json'));
        $this->app['files']->put($this->app->getBootstrapProvidersPath(), self::BOOTSTRAP_PROVIDERS);
    }

    protected function tearDown(): void
    {
        $this->app['files']->put($this->app->basePath('functional/test-layer/composer.json'), $this->originalComposerJson);
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));
        $this->app['files']->delete($this->app->getBootstrapProvidersPath());

        parent::tearDown();
    }

    public function test_the_layer_provider_is_registered_by_its_composer_json_only(): void
    {
        $this->artisan('osdd:provider', ['name' => 'OrdersServiceProvider', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertSame(self::BOOTSTRAP_PROVIDERS, $this->app['files']->get($this->app->getBootstrapProvidersPath()));
        $this->assertStringContainsString('Functional\\\\TestLayer\\\\Providers\\\\OrdersServiceProvider', $this->app['files']->get($this->app->basePath('functional/test-layer/composer.json')));
    }
}
