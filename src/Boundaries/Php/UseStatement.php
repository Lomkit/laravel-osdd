<?php

namespace Lomkit\Osdd\Boundaries\Php;

use Illuminate\Support\Str;

final class UseStatement
{
    /**
     * @var list<Import>
     */
    private array $imports = [];

    private function __construct(private readonly Tokens $tokens, private int $index) {}

    /**
     * @return array{list<Import>, int}
     */
    public static function parse(Tokens $tokens, int $useTokenIndex): array
    {
        $statement = new self($tokens, $useTokenIndex + 1);
        $statement->read();

        return [$statement->imports, $statement->index];
    }

    private function read(): void
    {
        $kind = $this->kind() ?? ImportKind::ClassLike;

        while ($this->tokens->isName($this->index)) {
            $this->readClause($kind);

            if (! $this->tokens->is($this->index, ',')) {
                return;
            }

            $this->index++;
        }
    }

    private function readClause(ImportKind $kind): void
    {
        $token = $this->tokens->at($this->index++);
        $name = ltrim((string) $token?->text, '\\');

        if ($this->tokens->is($this->index, T_NS_SEPARATOR) && $this->tokens->is($this->index + 1, '{')) {
            $this->index += 2;
            $this->readGroup($kind, $name);

            return;
        }

        $this->add($kind, $name, (int) $token?->line);
    }

    private function readGroup(ImportKind $kind, string $prefix): void
    {
        while ($this->index < $this->tokens->count() && ! $this->tokens->is($this->index, '}')) {
            $itemKind = $this->kind() ?? $kind;
            $token = $this->tokens->at($this->index++);

            if ($token?->is([T_STRING, T_NAME_QUALIFIED]) === true) {
                $this->add($itemKind, $prefix.'\\'.$token->text, $token->line);
            }
        }

        $this->index++;
    }

    private function add(ImportKind $kind, string $name, int $line): void
    {
        $alias = Str::afterLast($name, '\\');

        if ($this->tokens->is($this->index, T_AS) && $this->tokens->isName($this->index + 1)) {
            $alias = (string) $this->tokens->at($this->index + 1)?->text;
            $this->index += 2;
        }

        $this->imports[] = new Import($kind, $name, $alias, $line);
    }

    private function kind(): ?ImportKind
    {
        $kind = ImportKind::fromToken($this->tokens->at($this->index));

        if ($kind !== null) {
            $this->index++;
        }

        return $kind;
    }
}
