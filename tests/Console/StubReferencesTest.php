<?php

namespace Lomkit\Osdd\Tests\Console;

use Lomkit\Osdd\Tests\TestCase;
use Symfony\Component\Finder\Finder;

class StubReferencesTest extends TestCase
{
    private const array GENERATED_LAYER_ROOTS = ['Functional', 'Technical'];

    private const array EXISTING_CLASS_ROOTS = ['Illuminate', 'Lomkit'];

    public function test_stubs_reference_package_classes_only_under_the_lomkit_osdd_namespace(): void
    {
        $violations = [];

        foreach ($this->stubContents() as $stub => $contents) {
            foreach ($this->qualifiedNames($contents) as $name) {
                $violation = $this->violation($name);

                if ($violation !== null) {
                    $violations[] = "{$stub}: {$name} {$violation}";
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_stubs_require_the_lomkit_package(): void
    {
        $required = [];

        foreach ($this->stubContents() as $stub => $contents) {
            preg_match_all('#"[a-z0-9_.-]+/laravel-osdd"#', $contents, $matches);

            foreach ($matches[0] as $package) {
                $required[] = "{$stub}: {$package}";
            }
        }

        $this->assertSame([
            'layer/composer.stub: "lomkit/laravel-osdd"',
            'layer/osdd-composer.stub: "lomkit/laravel-osdd"',
        ], $required);
    }

    private function violation(string $name): ?string
    {
        $root = strstr($name, '\\', true);

        if (in_array($root, self::GENERATED_LAYER_ROOTS, true)) {
            return null;
        }

        if (! in_array($root, self::EXISTING_CLASS_ROOTS, true)) {
            return 'uses an unexpected root namespace';
        }

        if ($root === 'Lomkit' && ! str_starts_with($name, 'Lomkit\\Osdd\\')) {
            return 'is outside the package namespace';
        }

        if (! class_exists($name) && ! interface_exists($name) && ! trait_exists($name)) {
            return 'does not exist';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function stubContents(): array
    {
        $finder = (new Finder)->files()->name('*.stub')->in(dirname(__DIR__, 2).'/src/Console/stubs')->sortByName();

        $contents = [];

        foreach ($finder as $file) {
            $contents[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
        }

        return $contents;
    }

    /**
     * @return list<string>
     */
    private function qualifiedNames(string $contents): array
    {
        preg_match_all('/(?<![\w\\\\])[A-Z]\w*(?:\\\\{1,2}[A-Z]\w*)+/', $contents, $matches);

        return array_values(array_unique(array_map(
            fn (string $name): string => str_replace('\\\\', '\\', $name),
            $matches[0],
        )));
    }
}
