<?php

namespace Lomkit\Osdd\Tests\Boundaries;

use Lomkit\Osdd\Boundaries\Php\ClassReference;
use Lomkit\Osdd\Boundaries\Php\ReferencedNames;
use PHPUnit\Framework\TestCase;

class ReferencedNamesTest extends TestCase
{
    public function test_it_reads_class_function_and_constant_imports_with_their_lines(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            use Functional\Billing\Models\Invoice;
            use function Functional\Billing\format_amount;
            use const Functional\Billing\CURRENCY;
            use Functional\Users\Models\User as Customer, \Technical\Core\Clock;
            PHP;

        $this->assertSame([
            ['Functional\Billing\Models\Invoice', 5],
            ['Functional\Billing\format_amount', 6],
            ['Functional\Billing\CURRENCY', 7],
            ['Functional\Users\Models\User', 8],
            ['Technical\Core\Clock', 8],
        ], $this->references($code));
    }

    public function test_it_expands_group_uses_with_mixed_kinds_and_aliases(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            use Functional\Billing\{
                Models\Invoice,
                Models\Payment as Settlement,
                function format_amount,
                const CURRENCY,
            };
            use Technical\{Clock};
            PHP;

        $this->assertSame([
            ['Functional\Billing\Models\Invoice', 6],
            ['Functional\Billing\Models\Payment', 7],
            ['Functional\Billing\format_amount', 8],
            ['Functional\Billing\CURRENCY', 9],
            ['Technical\Clock', 11],
        ], $this->references($code));
    }

    public function test_it_resolves_qualified_names_through_aliases_and_the_current_namespace(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            use Functional\Billing as Bill;

            $invoice = new Bill\Models\Invoice;
            $order = new Models\Order;
            $clock = namespace\Clock::class;
            PHP;

        $this->assertSame([
            ['Functional\Billing', 5],
            ['Functional\Billing\Models\Invoice', 7],
            ['Functional\Orders\Models\Order', 8],
            ['Functional\Orders\Clock', 9],
        ], $this->references($code));
    }

    public function test_it_reads_fully_qualified_names_class_constants_and_attributes(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            #[\Functional\Billing\Attributes\Billable]
            class Order extends \Functional\Billing\Models\Model implements \Technical\Core\Contracts\Clock
            {
                public function invoice(): \Functional\Billing\Models\Invoice
                {
                    return \Functional\Billing\Models\Invoice::query()->first() ?? new \stdClass;
                }

                public array $casts = ['status' => \Functional\Billing\Enums\Status::class];
            }
            PHP;

        $this->assertSame([
            ['Functional\Billing\Attributes\Billable', 5],
            ['Functional\Billing\Models\Model', 6],
            ['Technical\Core\Contracts\Clock', 6],
            ['Functional\Billing\Models\Invoice', 8],
            ['Functional\Billing\Models\Invoice', 10],
            ['stdClass', 10],
            ['Functional\Billing\Enums\Status', 13],
        ], $this->references($code));
    }

    public function test_it_ignores_comments_docblocks_strings_and_unqualified_names(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            /**
             * @see \Functional\Billing\Models\Invoice
             */
            class Order
            {
                // \Functional\Billing\Models\Payment
                public string $model = 'Functional\Billing\Models\Invoice';

                public function total(): Invoice
                {
                    return "{$this->model} \Functional\Billing\Models\Invoice";
                }
            }
            PHP;

        $this->assertSame([], $this->references($code));
    }

    public function test_closure_and_trait_uses_are_not_imports(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders;

            use Functional\Billing\Concerns;

            class Order
            {
                use Concerns\HasInvoices;
                use \Technical\Core\Concerns\HasClock;

                public function total(): int
                {
                    $tax = 2;

                    return array_sum(array_map(function (int $line) use ($tax) {
                        return $line + $tax;
                    }, [1]));
                }
            }

            $outside = fn () => 1;
            $closure = function () use ($outside) {
                return $outside();
            };
            PHP;

        $this->assertSame([
            ['Functional\Billing\Concerns', 5],
            ['Functional\Billing\Concerns\HasInvoices', 9],
            ['Technical\Core\Concerns\HasClock', 10],
        ], $this->references($code));
    }

    public function test_braced_namespaces_keep_their_own_imports(): void
    {
        $code = <<<'PHP'
            <?php

            namespace Functional\Orders {
                use Functional\Billing as Imported;

                new Imported\Invoice;
            }

            namespace Functional\Users {
                new Imported\Invoice;
            }

            namespace {
                use Technical\Core\Clock;

                new Models\User;
            }
            PHP;

        $this->assertSame([
            ['Functional\Billing', 4],
            ['Functional\Billing\Invoice', 6],
            ['Functional\Users\Imported\Invoice', 10],
            ['Technical\Core\Clock', 14],
            ['Models\User', 16],
        ], $this->references($code));
    }

    public function test_a_file_without_namespace_resolves_against_the_global_namespace(): void
    {
        $code = <<<'PHP'
            <?php

            use Illuminate\Support\Facades\Route;

            Route::get('/orders', [Functional\Orders\Http\Controllers\OrderController::class, 'index']);
            PHP;

        $this->assertSame([
            ['Illuminate\Support\Facades\Route', 3],
            ['Functional\Orders\Http\Controllers\OrderController', 5],
        ], $this->references($code));
    }

    /**
     * @return list<array{string, int}>
     */
    private function references(string $code): array
    {
        return array_map(
            fn (ClassReference $reference): array => [$reference->name, $reference->line],
            ReferencedNames::inCode($code),
        );
    }
}
