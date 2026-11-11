<?php

namespace Lomkit\Osdd\Tests\Console\Commands\Make;

use Lomkit\Osdd\Tests\Fixtures\GeneratedClass;
use Lomkit\Osdd\Tests\TestCase;

class TestMakeCommandTest extends TestCase
{
    private const string APPLICATION_TEST_CASE = 'Tests\TestCase';

    public function test_a_feature_test_extends_the_test_case_of_the_application(): void
    {
        $this->declareApplicationTestCase();

        $this->artisan('osdd:test', ['name' => 'CheckoutTest', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $test = GeneratedClass::load(
            $this->project()->path('functional/test-layer/tests/Feature/CheckoutTest.php'),
            'Functional\TestLayer\Tests\Feature\CheckoutTest',
        );

        $this->assertSame(self::APPLICATION_TEST_CASE, $test->getParentClass() === false ? null : $test->getParentClass()->getName());
    }

    public function test_a_unit_test_extends_the_phpunit_test_case(): void
    {
        $this->artisan('osdd:test', ['name' => 'PriceTest', '--unit' => true, '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $test = GeneratedClass::load(
            $this->project()->path('functional/test-layer/tests/Unit/PriceTest.php'),
            'Functional\TestLayer\Tests\Unit\PriceTest',
        );

        $this->assertSame(\PHPUnit\Framework\TestCase::class, $test->getParentClass() === false ? null : $test->getParentClass()->getName());
    }

    private function declareApplicationTestCase(): void
    {
        if (class_exists(self::APPLICATION_TEST_CASE, false)) {
            return;
        }

        $path = $this->project()->path('tests/TestCase.php');
        $this->app['files']->ensureDirectoryExists(dirname($path));
        $this->app['files']->put($path, "<?php\n\nnamespace Tests;\n\nabstract class TestCase extends \\PHPUnit\\Framework\\TestCase\n{\n}\n");

        require $path;
    }
}
