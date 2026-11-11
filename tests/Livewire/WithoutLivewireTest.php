<?php

namespace Lomkit\Osdd\Tests\Livewire;

use Illuminate\Contracts\Console\Kernel;
use Lomkit\Osdd\Tests\Fixtures\LayerFixture;
use Lomkit\Osdd\Tests\TestCase;

class WithoutLivewireTest extends TestCase
{
    public function test_a_provider_calling_with_livewire_boots_when_livewire_does_not_run(): void
    {
        $provider = $this->layerFixture()->providerLayer(LayerFixture::bootMethod('$this->withLivewire();'));

        $provider->register($this->app);

        $this->assertTrue($this->app->providerIsLoaded($provider->class));
    }

    public function test_osdd_livewire_is_not_registered_when_livewire_does_not_run(): void
    {
        $this->assertArrayNotHasKey('osdd:livewire', $this->app->make(Kernel::class)->all());
    }
}
