<?php

namespace Lomkit\Osdd\Console\Providers\Source;

final readonly class CallSignature
{
    public string $helper;

    /**
     * @param  string  $arguments  The argument tokens without whitespace, comments or trailing commas, class names fully qualified
     */
    public function __construct(string $helper, public string $arguments)
    {
        $this->helper = strtolower($helper);
    }

    public function equals(self $other): bool
    {
        return $this->helper === $other->helper && $this->arguments === $other->arguments;
    }
}
