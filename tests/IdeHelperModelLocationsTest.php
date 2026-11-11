<?php

namespace Lomkit\Osdd\Tests;

use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;

class IdeHelperModelLocationsTest extends TestCase
{
    private string $tmpPath;

    #[\Override]
    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::withoutLayers();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $_SERVER['argv'][] = 'ide-helper:models';

        $this->tmpPath = $this->project()->root;

        mkdir($this->tmpPath.'/functional/billing/src/Models', 0755, true);

        file_put_contents($this->tmpPath.'/functional/billing/composer.json', json_encode([
            'name' => 'functional/billing',
            'type' => 'layer',
            'autoload' => ['psr-4' => ['Functional\\Billing\\' => 'src/']],
        ]));

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->tmpPath.'/functional',
        ]);

        // Simulate barryvdh/laravel-ide-helper being installed with its default config.
        $app['config']->set('ide-helper.model_locations', ['app']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $_SERVER['argv'] = array_filter($_SERVER['argv'], fn ($v) => $v !== 'ide-helper:models');
    }

    public function test_layer_source_path_is_added_to_model_locations(): void
    {
        $locations = $this->app['config']->get('ide-helper.model_locations', []);

        $this->assertContains($this->tmpPath.'/functional/billing/src', $locations);
    }

    public function test_existing_model_locations_are_preserved(): void
    {
        $locations = $this->app['config']->get('ide-helper.model_locations', []);

        $this->assertContains('app', $locations);
    }
}
