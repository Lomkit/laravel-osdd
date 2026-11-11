<?php

namespace Lomkit\Osdd\Tests\Generation;

use Lomkit\Osdd\Generation\ClassLocation;
use PHPUnit\Framework\TestCase;

class ClassLocationTest extends TestCase
{
    public function test_a_class_maps_to_a_file_under_the_directory(): void
    {
        $location = new ClassLocation('Functional\\Orders\\', '/app/functional/orders/src');

        $this->assertSame('/app/functional/orders/src/Models/Order.php', $location->fileOf('Functional\\Orders\\Models\\Order'));
    }

    public function test_a_nested_namespace_maps_to_nested_directories(): void
    {
        $location = new ClassLocation('Functional\\Orders\\Database\\Seeders\\', '/app/functional/orders/database/seeders');

        $this->assertSame('/app/functional/orders/database/seeders/Admin/AdminSeeder.php', $location->fileOf('Functional\\Orders\\Database\\Seeders\\Admin\\AdminSeeder'));
    }

    public function test_a_location_without_namespace_keeps_the_whole_class_path(): void
    {
        $location = new ClassLocation('', '/app/functional/orders/src');

        $this->assertSame('/app/functional/orders/src/Models/Order.php', $location->fileOf('Models\\Order'));
    }

    public function test_only_a_leading_namespace_is_stripped(): void
    {
        $location = new ClassLocation('Orders\\', '/app/functional/orders/src');

        $this->assertSame('/app/functional/orders/src/Functional/Orders/Order.php', $location->fileOf('Functional\\Orders\\Order'));
    }
}
