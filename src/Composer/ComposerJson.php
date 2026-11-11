<?php

namespace Lomkit\Osdd\Composer;

use JsonException;
use stdClass;

final class ComposerJson
{
    private const int ENCODING = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR;

    private const string DEFAULT_INDENT = '    ';

    private function __construct(
        public readonly string $path,
        private readonly stdClass $root,
        private readonly string $indent,
    ) {}

    /**
     * @throws MalformedComposerJsonException
     */
    public static function decode(string $json, string $path): self
    {
        try {
            $root = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw MalformedComposerJsonException::invalidJson($path, $exception->getMessage());
        }

        if (! $root instanceof stdClass) {
            throw MalformedComposerJsonException::notAnObject($path);
        }

        $indent = preg_match('#^([ \t]+)"#m', $json, $match) === 1 ? $match[1] : self::DEFAULT_INDENT;

        return new self($path, $root, $indent);
    }

    public static function blank(string $path): self
    {
        return new self($path, new stdClass, self::DEFAULT_INDENT);
    }

    public function encode(): string
    {
        $json = json_encode($this->root, self::ENCODING);

        if ($this->indent !== self::DEFAULT_INDENT) {
            $json = (string) preg_replace_callback(
                '#^(?: {4})+#m',
                fn (array $spaces): string => str_repeat($this->indent, intdiv(strlen($spaces[0]), 4)),
                $json,
            );
        }

        return $json.PHP_EOL;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return self::arrayOf($this->root);
    }

    public function find(string ...$keys): mixed
    {
        $node = $this->root;

        foreach ($keys as $key) {
            if (! $node instanceof stdClass || ! property_exists($node, $key)) {
                return null;
            }

            $node = $node->{$key};
        }

        return $node;
    }

    /**
     * @throws InvalidComposerJsonException
     */
    public function object(string ...$keys): stdClass
    {
        $node = $this->root;

        foreach ($keys as $key) {
            $value = $node->{$key} ?? new stdClass;

            if ($value === []) {
                $value = new stdClass;
            }

            if (! $value instanceof stdClass) {
                throw new InvalidComposerJsonException($this->path, $key);
            }

            $node = $node->{$key} = $value;
        }

        return $node;
    }

    public function set(string $key, mixed $value): void
    {
        $this->root->{$key} = $value;
    }

    /**
     * @param  stdClass|array<array-key, mixed>  $node
     * @return array<array-key, mixed>
     */
    private static function arrayOf(stdClass|array $node): array
    {
        return array_map(
            fn (mixed $value): mixed => $value instanceof stdClass || is_array($value) ? self::arrayOf($value) : $value,
            $node instanceof stdClass ? get_object_vars($node) : $node,
        );
    }
}
