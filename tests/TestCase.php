<?php

namespace Lomkit\Osdd\Tests;

use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\Fixtures\TemporaryProject;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use WithWorkbench;

    private ?TemporaryProject $project = null;

    protected function setUp(): void
    {
        parent::setUp();

        PromptState::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->project?->delete();
        $this->project = null;
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        $app['config']->set('view.compiled', $this->project()->path('storage/framework/views'));
    }

    #[\Override]
    protected function getApplicationBasePath()
    {
        return $this->project()->root;
    }

    protected function project(): TemporaryProject
    {
        return $this->project ??= $this->createProject();
    }

    protected function createProject(): TemporaryProject
    {
        return TemporaryProject::fromWorkbench();
    }

    protected function layerFixture(): LayerFixture
    {
        return new LayerFixture($this->app);
    }
}
