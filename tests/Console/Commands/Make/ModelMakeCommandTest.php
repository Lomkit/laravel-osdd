<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;
use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class ModelMakeCommandTest extends TestCase
{
    public function test_it_asks_for_the_layer_and_the_name(): void
    {
        $this->artisan('osdd:model')
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->expectsQuestion('What should the model be named?', 'Warehouse')
            ->expectsChoice('Would you like any of the following?', [], ['seed' => 'Database Seeder', 'factory' => 'Factory', 'requests' => 'Form Requests', 'migration' => 'Migration', 'policy' => 'Policy', 'resource' => 'Resource Controller'])
            ->assertExitCode(0);

        $this->assertFileExists($this->layerPath('src/Models/Warehouse.php'));
    }

    public function test_a_model_with_a_factory_makes_instances_through_it(): void
    {
        $this->artisan('osdd:model', ['name' => 'Customer', '--factory' => true, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        GeneratedClass::load($this->layerPath('database/factories/CustomerFactory.php'), 'Functional\TestLayer\Database\Factories\CustomerFactory');
        $model = GeneratedClass::load($this->layerPath('src/Models/Customer.php'), 'Functional\TestLayer\Models\Customer');

        $this->assertContains(HasFactory::class, class_uses($model->getName()));
        $this->assertInstanceOf($model->getName(), $model->getMethod('factory')->invoke(null)->make());
    }

    public function test_a_model_without_a_factory_does_not_use_one(): void
    {
        $this->artisan('osdd:model', ['name' => 'Supplier', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $model = GeneratedClass::load($this->layerPath('src/Models/Supplier.php'), 'Functional\TestLayer\Models\Supplier');

        $this->assertNotContains(HasFactory::class, class_uses($model->getName()));
        $this->assertSame([], $model->getAttributes());
    }

    public function test_all_companions_are_generated_in_the_layer(): void
    {
        $this->artisan('osdd:model', ['name' => 'Product', '--all' => true, '--requests' => true, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        foreach ([
            'src/Models/Product.php' => 'Functional\TestLayer\Models\Product',
            'database/factories/ProductFactory.php' => 'Functional\TestLayer\Database\Factories\ProductFactory',
            'database/seeders/ProductSeeder.php' => 'Functional\TestLayer\Database\Seeders\ProductSeeder',
            'src/Http/Requests/StoreProductRequest.php' => 'Functional\TestLayer\Http\Requests\StoreProductRequest',
            'src/Http/Requests/UpdateProductRequest.php' => 'Functional\TestLayer\Http\Requests\UpdateProductRequest',
            'src/Http/Controllers/ProductController.php' => 'Functional\TestLayer\Http\Controllers\ProductController',
            'src/Policies/ProductPolicy.php' => 'Functional\TestLayer\Policies\ProductPolicy',
        ] as $file => $class) {
            GeneratedClass::load($this->layerPath($file), $class);
        }

        $this->artisan('migrate', ['--path' => $this->layerPath('database/migrations'), '--realpath' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable('products'));
        $this->assertDirectoryDoesNotExist($this->app->path());
    }

    public function test_form_requests_alone_are_generated_in_the_layer(): void
    {
        $this->artisan('osdd:model', ['name' => 'Coupon', '--requests' => true, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        GeneratedClass::load($this->layerPath('src/Http/Requests/StoreCouponRequest.php'), 'Functional\TestLayer\Http\Requests\StoreCouponRequest');
        GeneratedClass::load($this->layerPath('src/Http/Requests/UpdateCouponRequest.php'), 'Functional\TestLayer\Http\Requests\UpdateCouponRequest');
        $this->assertFileDoesNotExist($this->layerPath('src/Http/Controllers/CouponController.php'));
    }

    private function layerPath(string $file): string
    {
        return $this->project()->path('functional/test-layer/'.$file);
    }
}
