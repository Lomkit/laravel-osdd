<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class FactoryMakeCommandTest extends TestCase
{
    public function test_a_name_without_the_factory_suffix_gets_the_file_of_its_class(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Parcel', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        GeneratedClass::load(
            $this->project()->path('functional/test-layer/database/factories/ParcelFactory.php'),
            'Functional\TestLayer\Database\Factories\ParcelFactory',
        );

        $this->assertFileDoesNotExist($this->project()->path('functional/test-layer/database/factories/Parcel.php'));
    }
}
