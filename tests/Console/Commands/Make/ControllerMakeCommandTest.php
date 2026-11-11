<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class ControllerMakeCommandTest extends TestCase
{
    public function test_a_resource_controller_has_the_resource_actions(): void
    {
        $this->artisan('osdd:controller', ['name' => 'WarehouseController', '--resource' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $controller = GeneratedClass::load(
            $this->project()->path('functional/test-layer/src/Http/Controllers/WarehouseController.php'),
            'Functional\TestLayer\Http\Controllers\WarehouseController',
        );

        foreach (['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'] as $action) {
            $this->assertTrue($controller->hasMethod($action), "The resource controller has no [{$action}] action.");
        }
    }

    public function test_form_requests_are_generated_in_the_layer_and_typed_on_the_actions(): void
    {
        $this->artisan('osdd:controller', ['name' => 'CarrierController', '--resource' => true, '--model' => 'Carrier', '--requests' => true, '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        GeneratedClass::load($this->requestPath('StoreCarrierRequest'), 'Functional\TestLayer\Http\Requests\StoreCarrierRequest');
        GeneratedClass::load($this->requestPath('UpdateCarrierRequest'), 'Functional\TestLayer\Http\Requests\UpdateCarrierRequest');
        $controller = GeneratedClass::load(
            $this->project()->path('functional/test-layer/src/Http/Controllers/CarrierController.php'),
            'Functional\TestLayer\Http\Controllers\CarrierController',
        );

        $this->assertSame('Functional\TestLayer\Http\Requests\StoreCarrierRequest', $this->parameterType($controller->getMethod('store'), 0));
        $this->assertSame('Functional\TestLayer\Http\Requests\UpdateCarrierRequest', $this->parameterType($controller->getMethod('update'), 0));
        $this->assertSame('Functional\TestLayer\Models\Carrier', $this->parameterType($controller->getMethod('show'), 0));
    }

    private function requestPath(string $request): string
    {
        return $this->project()->path("functional/test-layer/src/Http/Requests/{$request}.php");
    }

    private function parameterType(\ReflectionMethod $method, int $position): string
    {
        $type = $method->getParameters()[$position]->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : '';
    }
}
