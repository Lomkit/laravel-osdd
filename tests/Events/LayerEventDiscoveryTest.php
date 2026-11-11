<?php

namespace Lomkit\Osdd\Tests\Events;

use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Lomkit\Osdd\Events\LayerEventServiceProvider;
use Lomkit\Osdd\Tests\Fixtures\ListenerCalls;
use Lomkit\Osdd\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SplFileInfo;

class LayerEventDiscoveryTest extends TestCase
{
    private static ?bool $discover = true;

    /**
     * @var list<ListenerLayer>
     */
    private array $listenerLayers = [];

    protected function setUp(): void
    {
        parent::setUp();

        ListenerCalls::$calls = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->listenerLayers as $layer) {
            $layer->unregister();
        }

        self::$discover = true;
        FixtureEventsProvider::reset();
        DiscoverEvents::$guessClassNamesUsingCallback = null;

        parent::tearDown();
    }

    #[\Override]
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), FixtureEventsProvider::class];
    }

    #[\Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        if (self::$discover !== null) {
            $app['config']->set('osdd.events.discover', self::$discover);
        }
    }

    public function test_a_listener_of_a_layer_handles_the_dispatched_event(): void
    {
        $layer = $this->listenerLayer();
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    public function test_listeners_in_sub_folders_are_discovered(): void
    {
        $layer = $this->listenerLayer();
        $layer->writeListener('Billing\\SendReceipt');
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertEqualsCanonicalizing(
            [$layer->listener().'@handle', $layer->listener('Billing\\SendReceipt').'@handle'],
            ListenerCalls::$calls,
        );
    }

    public function test_a_file_that_does_not_declare_its_psr_4_class_is_skipped(): void
    {
        $layer = $this->listenerLayer();
        $this->app['files']->put($layer->listenersDirectory().'/helpers.php', "<?php\n\nfunction listener_helper(): void {}\n");
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    public function test_the_listeners_folder_follows_the_layer_psr_4_directory(): void
    {
        $layer = $this->listenerLayer('lib');
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    public function test_event_cache_stores_the_layer_listeners_and_the_next_process_reads_it(): void
    {
        $layer = $this->listenerLayer();
        $this->refreshApplication();

        $this->artisan('event:cache')->assertExitCode(0);
        $cache = require $this->app->getCachedEventsPath();

        $this->assertSame([$layer->event() => [$layer->listener().'@handle']], $cache[LayerEventServiceProvider::class]);

        $this->app['files']->deleteDirectory($layer->listenersDirectory());
        $this->refreshApplication();
        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    public function test_optimize_caches_the_layer_listeners(): void
    {
        $layer = $this->listenerLayer();
        $this->refreshApplication();

        Artisan::call('optimize', ['--except' => 'config,routes,views,osdd']);
        $cache = require $this->app->getCachedEventsPath();

        $this->assertSame([$layer->event() => [$layer->listener().'@handle']], $cache[LayerEventServiceProvider::class]);
    }

    public function test_without_discovery_nothing_is_registered_nor_cached(): void
    {
        self::$discover = false;
        $layer = $this->listenerLayer();
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));
        $this->artisan('event:cache')->assertExitCode(0);
        $cache = require $this->app->getCachedEventsPath();

        $this->assertSame([], ListenerCalls::$calls);
        $this->assertSame([], $this->app->getProviders(LayerEventServiceProvider::class));
        $this->assertArrayNotHasKey(LayerEventServiceProvider::class, $cache);
    }

    public function test_discovery_turned_on_by_a_layer_config_override_is_honoured(): void
    {
        self::$discover = null;
        FixtureEventsProvider::$discoverInRegister = true;
        $layer = $this->listenerLayer();
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function handWrittenForms(): iterable
    {
        yield 'class name' => ['class'];
        yield 'class@handle' => ['class@handle'];
        yield 'callable array' => ['array'];
    }

    #[DataProvider('handWrittenForms')]
    public function test_a_listener_also_registered_by_hand_runs_once(string $form): void
    {
        $layer = $this->listenerLayer();
        FixtureEventsProvider::$listenedByHand = [[$layer->event(), match ($form) {
            'class' => $layer->listener(),
            'class@handle' => $layer->listener().'@handle',
            default => [$layer->listener(), 'handle'],
        }]];
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertCount(1, ListenerCalls::$calls);
    }

    public function test_a_listener_registered_by_hand_for_another_event_is_still_discovered(): void
    {
        $layer = $this->listenerLayer();
        FixtureEventsProvider::$listenedByHand = [['orders.shipped', $layer->listener()]];
        $this->refreshApplication();

        Event::dispatch(new ($layer->event()));

        $this->assertSame([$layer->listener().'@handle'], ListenerCalls::$calls);
    }

    public function test_the_class_name_guess_of_laravel_is_left_as_it_was(): void
    {
        $this->listenerLayer();
        $this->refreshApplication();
        $guess = fn (SplFileInfo $file, string $basePath): string => 'App\\Listeners\\Guessed';
        DiscoverEvents::guessClassNamesUsing($guess);

        $discovered = $this->app->getProvider(LayerEventServiceProvider::class)?->discoverEvents();

        $this->assertNotEmpty($discovered);
        $this->assertSame($guess, DiscoverEvents::$guessClassNamesUsingCallback);
    }

    public function test_the_class_name_guess_stays_unset_after_discovery(): void
    {
        $this->listenerLayer();
        $this->refreshApplication();

        $this->assertNull(DiscoverEvents::$guessClassNamesUsingCallback);
    }

    private function listenerLayer(string $sourceFolder = 'src'): ListenerLayer
    {
        return $this->listenerLayers[] = ListenerLayer::create($this->project()->root, $sourceFolder);
    }
}
