<?php

namespace Lomkit\Osdd\Console\Providers\Source;

use Lomkit\Osdd\Console\Providers\Calls\PresenceRule;
use Lomkit\Osdd\Console\Providers\Calls\ProviderCall;
use Lomkit\Osdd\Console\Providers\Calls\ProviderFile;
use Lomkit\Osdd\Console\Providers\LayerProvider;
use PhpToken;

final readonly class ProviderSource
{
    private const string THIS = '$this';

    private const array NAME_TOKENS = [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED];

    private const array RELATIVE_CLASS_NAMES = ['self', 'parent', 'static'];

    /**
     * @param  list<CallSignature>  $calls  The $this->helper(...) calls of the source
     */
    private function __construct(
        public string $code,
        public ProviderImports $imports,
        private array $calls,
    ) {}

    public static function parse(string $code): self
    {
        $tokens = array_values(array_filter(PhpToken::tokenize($code), fn (PhpToken $token): bool => ! $token->isIgnorable()));
        $imports = ProviderImports::fromTokens($tokens);

        return new self($code, $imports, self::callsIn($tokens, $imports));
    }

    /**
     * Whether the provider already makes the call: the same helper with the same arguments, or any call to the helper.
     */
    public function contains(ProviderCall $call, LayerProvider $provider): bool
    {
        if ($call->presence === PresenceRule::SameHelper) {
            return $this->callsHelper(strtolower($call->helper));
        }

        foreach (self::parse('<?php '.$call->render(ProviderFile::withoutImports($provider)))->calls as $expected) {
            foreach ($this->calls as $existing) {
                if ($existing->equals($expected)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function callsHelper(string $helper): bool
    {
        foreach ($this->calls as $call) {
            if ($call->helper === $helper) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<PhpToken>  $tokens
     * @return list<CallSignature>
     */
    private static function callsIn(array $tokens, ProviderImports $imports): array
    {
        $calls = [];

        foreach ($tokens as $index => $token) {
            $isCall = $token->is(T_VARIABLE) && $token->text === self::THIS
                && ($tokens[$index + 1] ?? null)?->is([T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR])
                && ($tokens[$index + 2] ?? null)?->is(T_STRING)
                && ($tokens[$index + 3] ?? null)?->is('(');

            if ($isCall) {
                $calls[] = new CallSignature($tokens[$index + 2]->text, self::argumentsFrom($tokens, $index + 4, $imports));
            }
        }

        return $calls;
    }

    /**
     * @param  list<PhpToken>  $tokens
     */
    private static function argumentsFrom(array $tokens, int $start, ProviderImports $imports): string
    {
        $arguments = '';
        $depth = 1;

        for ($index = $start; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];
            $next = $tokens[$index + 1] ?? null;
            $depth += $token->is('(') ? 1 : ($token->is(')') ? -1 : 0);

            if ($depth === 0) {
                break;
            }

            if ($token->is(',') && $next?->is([')', ']'])) {
                continue;
            }

            $arguments .= self::isClassName($token, $next) ? $imports->resolve($token->text) : $token->text;
        }

        return $arguments;
    }

    private static function isClassName(PhpToken $token, ?PhpToken $next): bool
    {
        return $token->is(self::NAME_TOKENS)
            && $next?->is(T_DOUBLE_COLON) === true
            && ! in_array(strtolower($token->text), self::RELATIVE_CLASS_NAMES, true);
    }
}
