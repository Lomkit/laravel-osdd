<?php

namespace Lomkit\Osdd\Tests\Composer;

use Lomkit\Osdd\Composer\ComposerJson;
use Lomkit\Osdd\Composer\InvalidComposerJsonException;
use Lomkit\Osdd\Composer\MalformedComposerJsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ComposerJsonTest extends TestCase
{
    private const string PATH = '/project/composer.json';

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedFiles(): array
    {
        return [
            'broken json' => ['{"name": "functional/orders",', 'it is not valid JSON (Syntax error).'],
            'a string' => ['"layer"', 'it is not a JSON object.'],
            'a list' => ['["functional/orders"]', 'it is not a JSON object.'],
        ];
    }

    #[DataProvider('malformedFiles')]
    public function test_a_file_that_is_not_a_json_object_is_refused_with_its_path(string $json, string $reason): void
    {
        try {
            ComposerJson::decode($json, self::PATH);
            $this->fail('The file was accepted.');
        } catch (MalformedComposerJsonException $exception) {
            $this->assertSame(self::PATH, $exception->path);
            $this->assertSame($reason, $exception->reason);
            $this->assertSame('Cannot read ['.self::PATH.']: '.$reason, $exception->getMessage());
        }
    }

    public function test_it_gives_the_contents_as_arrays_at_every_depth(): void
    {
        $json = '{"name": "functional/orders", "require": {}, "autoload": {"psr-4": {"Functional\\\\Orders\\\\": ["src/", "lib/"]}}, "extra": {"laravel": {"providers": ["A\\\\B"]}}}';

        $this->assertSame(json_decode($json, true), ComposerJson::decode($json, self::PATH)->toArray());
    }

    public function test_it_encodes_back_with_the_indentation_it_was_read_with(): void
    {
        $json = "{\n\t\"require\": {\n\t\t\"php\": \"^8.3\"\n\t},\n\t\"require-dev\": {}\n}\n";

        $this->assertSame($json, ComposerJson::decode($json, self::PATH)->encode());
        $this->assertSame('{}'.PHP_EOL, ComposerJson::blank(self::PATH)->encode());
    }

    public function test_it_finds_nested_values_and_creates_missing_objects(): void
    {
        $composer = ComposerJson::decode('{"config": {"sort-packages": true}, "extra": []}', self::PATH);

        $this->assertTrue($composer->find('config', 'sort-packages'));
        $this->assertNull($composer->find('config', 'platform'));
        $this->assertNull($composer->find('config', 'sort-packages', 'deeper'));

        $composer->object('extra', 'laravel')->providers = ['A\\B'];

        $this->assertEquals((object) ['providers' => ['A\\B']], $composer->find('extra', 'laravel'));
        $this->assertInstanceOf(stdClass::class, $composer->object());
    }

    public function test_it_refuses_to_create_an_object_over_another_value(): void
    {
        $this->expectException(InvalidComposerJsonException::class);
        $this->expectExceptionMessage('Cannot edit ['.self::PATH.']: "extra" must be a JSON object.');

        ComposerJson::decode('{"extra": "none"}', self::PATH)->object('extra', 'laravel');
    }
}
