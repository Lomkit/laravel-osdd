<?php

namespace Lomkit\Osdd\Tests\Composer;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Lomkit\Osdd\Composer\ComposerUpdate;
use Lomkit\Osdd\Composer\ProcessComposerUpdate;
use Lomkit\Osdd\Tests\Fakes\FakeComposerBinary;
use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ProcessComposerUpdateTest extends TestCase
{
    private string $workingPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workingPath = sys_get_temp_dir().'/osdd-composer-update-'.uniqid();
        mkdir($this->workingPath, 0755, true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->workingPath);

        parent::tearDown();
    }

    public function test_it_updates_the_packages_with_the_given_binary_and_streams_its_output(): void
    {
        $binary = new FakeComposerBinary(output: 'Updating functional/orders');
        $output = new BufferedOutput;

        try {
            $succeeded = $this->composerUpdate($binary->path)->update(['functional/orders', 'technical/billing'], $output);

            $this->assertTrue($succeeded);
            $this->assertSame(['update functional/orders technical/billing'], $binary->calls());
            $this->assertSame('Updating functional/orders', $output->fetch());
        } finally {
            $binary->delete();
        }
    }

    public function test_a_failing_update_returns_false(): void
    {
        $binary = new FakeComposerBinary(exitCode: 2);

        try {
            $this->assertFalse($this->composerUpdate($binary->path)->update(['functional/orders'], new BufferedOutput));
            $this->assertSame(['update functional/orders'], $binary->calls());
        } finally {
            $binary->delete();
        }
    }

    public function test_it_runs_the_composer_phar_of_the_project_when_no_binary_is_given(): void
    {
        $phar = new FakeComposerBinary;

        try {
            copy($phar->path, $this->workingPath.'/composer.phar');

            $this->assertTrue($this->composerUpdate(null)->update(['functional/orders'], new BufferedOutput));
            $this->assertSame(['update functional/orders'], $phar->calls());
        } finally {
            $phar->delete();
        }
    }

    public function test_the_container_runs_composer_in_the_project(): void
    {
        $this->assertInstanceOf(ProcessComposerUpdate::class, $this->app->make(ComposerUpdate::class));
    }

    private function composerUpdate(?string $binary): ProcessComposerUpdate
    {
        return new ProcessComposerUpdate(new Composer(new Filesystem, $this->workingPath), $this->workingPath, $binary);
    }
}
