<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class PolicyMakeCommandTest extends TestCase
{
    public function test_a_model_policy_has_the_abilities_typed_on_the_layer_model_and_the_user_model(): void
    {
        $this->app['config']->set('auth.providers.users.model', 'Functional\Users\Models\User');

        $this->artisan('osdd:policy', ['name' => 'ShipmentPolicy', '--model' => 'Shipment', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $policy = GeneratedClass::load(
            $this->project()->path('functional/test-layer/src/Policies/ShipmentPolicy.php'),
            'Functional\TestLayer\Policies\ShipmentPolicy',
        );

        foreach (['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $this->assertTrue($policy->hasMethod($ability), "The policy has no [{$ability}] ability.");
            $this->assertSame('Functional\Users\Models\User', $this->parameterType($policy->getMethod($ability), 0));
        }

        $this->assertSame('Functional\TestLayer\Models\Shipment', $this->parameterType($policy->getMethod('view'), 1));
    }

    public function test_a_policy_without_a_model_has_no_ability(): void
    {
        $this->artisan('osdd:policy', ['name' => 'ReportingPolicy', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $policy = GeneratedClass::load(
            $this->project()->path('functional/test-layer/src/Policies/ReportingPolicy.php'),
            'Functional\TestLayer\Policies\ReportingPolicy',
        );

        $this->assertFalse($policy->hasMethod('viewAny'));
    }

    private function parameterType(\ReflectionMethod $method, int $position): string
    {
        $type = $method->getParameters()[$position]->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : '';
    }
}
