<?php

namespace Lomkit\Osdd\Tests\Console\Providers\Source;

use Lomkit\Osdd\Console\Providers\Source\DeclaredParent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeclaredParentTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function sources(): iterable
    {
        yield 'imported parent' => ["use Lomkit\\Osdd\\LayerServiceProvider;\n\nclass OrdersServiceProvider extends LayerServiceProvider {}", 'Lomkit\Osdd\LayerServiceProvider'];
        yield 'aliased parent' => ["use Lomkit\\Osdd\\LayerServiceProvider as Base;\n\nclass OrdersServiceProvider extends Base {}", 'Lomkit\Osdd\LayerServiceProvider'];
        yield 'fully qualified parent' => ['class OrdersServiceProvider extends \Lomkit\Osdd\LayerServiceProvider {}', 'Lomkit\Osdd\LayerServiceProvider'];
        yield 'parent in the same namespace' => ['class OrdersServiceProvider extends BaseProvider {}', 'Functional\Orders\Providers\BaseProvider'];
        yield 'class constant before the class' => ["use Lomkit\\Osdd\\LayerServiceProvider;\n\n\$parent = LayerServiceProvider::class;\n\nclass OrdersServiceProvider extends LayerServiceProvider {}", 'Lomkit\Osdd\LayerServiceProvider'];
        yield 'another class of the file' => ["class Helper extends \\Lomkit\\Osdd\\LayerServiceProvider {}\n\nclass OrdersServiceProvider {}", null];
        yield 'no parent' => ['class OrdersServiceProvider {}', null];
        yield 'no class' => ['function orders(): void {}', null];
    }

    #[DataProvider('sources')]
    public function test_it_resolves_the_parent_of_the_class(string $body, ?string $parent): void
    {
        $code = "<?php\n\nnamespace Functional\\Orders\\Providers;\n\n{$body}\n";

        $this->assertSame($parent, DeclaredParent::of($code, 'OrdersServiceProvider'));
    }
}
