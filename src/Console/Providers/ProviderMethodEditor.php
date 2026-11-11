<?php

namespace Lomkit\Osdd\Console\Providers;

use PhpToken;

final class ProviderMethodEditor
{
    private const string EMPTY_BODY_PLACEHOLDER = '//';

    /**
     * Append a statement at the end of a method body of the class in $source.
     *
     * @throws ProviderMethodNotFoundException
     */
    public function append(string $source, ProviderMethod $method, string $statement): string
    {
        $tokens = array_values(PhpToken::tokenize($source));
        $function = $this->findMethod($tokens, $method);
        $open = $this->findBodyOpening($tokens, $function, $method);
        $close = $this->findMatchingBrace($tokens, $open, $method);

        $openPosition = $tokens[$open]->pos;
        $closePosition = $tokens[$close]->pos;
        $body = substr($source, $openPosition + 1, $closePosition - $openPosition - 1);

        $indent = $this->lineIndentation($source, $tokens[$function]->pos);
        $innerIndent = $indent.(str_contains($indent, "\t") ? "\t" : '    ');
        $kept = in_array(trim($body), ['', self::EMPTY_BODY_PLACEHOLDER], true) ? '' : rtrim($body);

        return substr($source, 0, $openPosition + 1)
            .$kept."\n".$innerIndent.$statement."\n".$indent
            .substr($source, $closePosition);
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private function findMethod(array $tokens, ProviderMethod $method): int
    {
        $depth = 0;

        foreach ($tokens as $index => $token) {
            $depth += $this->braceDelta($token);

            if ($depth === 1 && $token->is(T_FUNCTION) && $this->nameAfter($tokens, $index) === $method->value) {
                return $index;
            }
        }

        throw new ProviderMethodNotFoundException($method);
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private function nameAfter(array $tokens, int $function): ?string
    {
        for ($index = $function + 1; $index < count($tokens); $index++) {
            if ($tokens[$index]->isIgnorable() || $tokens[$index]->is('&')) {
                continue;
            }

            return $tokens[$index]->is(T_STRING) ? strtolower($tokens[$index]->text) : null;
        }

        return null;
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private function findBodyOpening(array $tokens, int $function, ProviderMethod $method): int
    {
        for ($index = $function + 1; $index < count($tokens); $index++) {
            if ($tokens[$index]->is(';')) {
                break;
            }

            if ($tokens[$index]->is('{')) {
                return $index;
            }
        }

        throw new ProviderMethodNotFoundException($method);
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private function findMatchingBrace(array $tokens, int $open, ProviderMethod $method): int
    {
        $depth = 0;

        for ($index = $open; $index < count($tokens); $index++) {
            $depth += $this->braceDelta($tokens[$index]);

            if ($depth === 0) {
                return $index;
            }
        }

        throw new ProviderMethodNotFoundException($method);
    }

    private function braceDelta(PhpToken $token): int
    {
        if ($token->is(['{', T_DOLLAR_OPEN_CURLY_BRACES])) {
            return 1;
        }

        return $token->is('}') ? -1 : 0;
    }

    private function lineIndentation(string $source, int $position): string
    {
        $lineStart = strrpos(substr($source, 0, $position), "\n");
        $line = substr($source, $lineStart === false ? 0 : $lineStart + 1);

        return preg_match('/^[ \t]*/', $line, $match) === 1 ? $match[0] : '';
    }
}
