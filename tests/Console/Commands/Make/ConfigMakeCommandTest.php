<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

class ConfigMakeCommandTest extends TestCase
{
    private const string PROVIDER_METHODS = <<<'PHP'
            public function boot(): void
            {
                //
            }

            public function register(): void
            {
                //
            }
        PHP;

    public function test_the_layer_provider_overrides_the_config_with_the_generated_file(): void
    {
        $provider = $this->layerFixture()->providerLayer(self::PROVIDER_METHODS);

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        file_put_contents($this->configPath('payments.php'), "<?php\n\nreturn ['driver' => 'layer'];\n");
        $this->app['config']->set('payments', ['driver' => 'default', 'currency' => 'EUR']);
        $provider->register($this->app);

        $this->assertSame(['driver' => 'layer', 'currency' => 'EUR'], $this->app['config']->get('payments'));
    }

    public function test_the_php_extension_is_not_doubled(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments.php', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/config/payments.php'));
        $this->assertFileDoesNotExist($this->project()->path('functional/test-layer/config/payments.php.php'));
    }

    public function test_a_config_generated_twice_is_registered_once(): void
    {
        $provider = $this->layerFixture()->providerLayer(self::PROVIDER_METHODS);

        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => LayerFixture::PROVIDER_LAYER])->assertExitCode(0);
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => LayerFixture::PROVIDER_LAYER, '--force' => true])->assertExitCode(0);

        $this->assertSame(1, substr_count($provider->source(), 'overrideConfigFrom('));
    }

    public function test_without_a_provider_it_writes_the_config_and_prints_the_line_to_add(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments', '--layer' => 'functional/test-layer'])
            ->expectsOutputToContain('Layer [functional/test-layer] has no service provider.')
            ->expectsOutputToContain("\$this->overrideConfigFrom(__DIR__ . '/../../config/payments.php', 'payments');")
            ->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/config/payments.php'));
    }

    public function test_it_asks_for_the_layer_when_none_is_given(): void
    {
        $this->artisan('osdd:config', ['name' => 'payments'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileExists($this->project()->path('functional/test-layer/config/payments.php'));
    }

    private function configPath(string $file): string
    {
        return $this->project()->path(LayerFixture::PROVIDER_LAYER.'/config/'.$file);
    }
}
