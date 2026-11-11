<?php

namespace Lomkit\Osdd\Tests\Console\Providers;

use Lomkit\Osdd\Console\Providers\ConventionalProvider;
use Lomkit\Osdd\Layers\LayerName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConventionalProviderTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function names(): iterable
    {
        yield 'single word' => ['functional/orders', 'OrdersServiceProvider'];
        yield 'dashes' => ['functional/order-lines', 'OrderLinesServiceProvider'];
        yield 'underscores' => ['functional/order_lines', 'OrderLinesServiceProvider'];
        yield 'dots' => ['functional/foo.bar', 'FooBarServiceProvider'];
    }

    #[DataProvider('names')]
    public function test_the_class_is_named_after_the_package_like_the_layer_namespace(string $layer, string $className): void
    {
        $provider = new ConventionalProvider(LayerName::fromString($layer));

        $this->assertSame($className, $provider->className);
        $this->assertSame("Providers/{$className}.php", $provider->sourceFile());
        $this->assertSame(LayerName::fromString($layer)->namespace()."\\Providers\\{$className}", $provider->classIn(LayerName::fromString($layer)->namespace().'\\'));
    }
}
