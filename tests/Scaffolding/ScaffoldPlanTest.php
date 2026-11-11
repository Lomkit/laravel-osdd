<?php

namespace Lomkit\Osdd\Tests\Scaffolding;

use Lomkit\Osdd\Scaffolding\DuplicateScaffoldKeyException;
use Lomkit\Osdd\Scaffolding\LayerScaffold;
use Lomkit\Osdd\Scaffolding\ScaffoldDependencyCycleException;
use Lomkit\Osdd\Scaffolding\ScaffoldPlan;
use Lomkit\Osdd\Tests\Scaffolding\Fixtures\KeyedScaffold;
use PHPUnit\Framework\TestCase;

class ScaffoldPlanTest extends TestCase
{
    public function test_a_scaffold_runs_after_the_scaffolds_it_depends_on(): void
    {
        $plan = ScaffoldPlan::of([
            new KeyedScaffold('migration', ['provider']),
            new KeyedScaffold('policy', ['model']),
            new KeyedScaffold('model'),
            new KeyedScaffold('provider'),
        ]);

        $this->assertSame(['provider', 'migration', 'model', 'policy'], $this->keys($plan));
    }

    public function test_it_keeps_the_given_order_of_independent_scaffolds(): void
    {
        $plan = ScaffoldPlan::of([new KeyedScaffold('routes'), new KeyedScaffold('test'), new KeyedScaffold('controller')]);

        $this->assertSame(['routes', 'test', 'controller'], $this->keys($plan));
    }

    public function test_a_dependency_outside_the_plan_is_ignored(): void
    {
        $plan = ScaffoldPlan::of([new KeyedScaffold('seeder', ['provider']), new KeyedScaffold('factory', ['model'])]);

        $this->assertSame(['seeder', 'factory'], $this->keys($plan));
    }

    public function test_a_shared_dependency_runs_once(): void
    {
        $plan = ScaffoldPlan::of([
            new KeyedScaffold('migration', ['provider']),
            new KeyedScaffold('seeder', ['provider']),
            new KeyedScaffold('provider'),
        ]);

        $this->assertSame(['provider', 'migration', 'seeder'], $this->keys($plan));
    }

    public function test_it_refuses_two_scaffolds_with_the_same_key(): void
    {
        $this->expectException(DuplicateScaffoldKeyException::class);
        $this->expectExceptionMessage('Two scaffolds share the key [model].');

        ScaffoldPlan::of([new KeyedScaffold('model'), new KeyedScaffold('model')]);
    }

    public function test_it_refuses_a_dependency_cycle_and_names_it(): void
    {
        $this->expectException(ScaffoldDependencyCycleException::class);
        $this->expectExceptionMessage('The scaffolds depend on each other: model -> factory -> policy -> model.');

        ScaffoldPlan::of([
            new KeyedScaffold('routes'),
            new KeyedScaffold('model', ['factory']),
            new KeyedScaffold('factory', ['policy']),
            new KeyedScaffold('policy', ['model']),
        ]);
    }

    /**
     * @return list<string>
     */
    private function keys(ScaffoldPlan $plan): array
    {
        return array_map(fn (LayerScaffold $scaffold): string => $scaffold->key(), $plan->steps());
    }
}
