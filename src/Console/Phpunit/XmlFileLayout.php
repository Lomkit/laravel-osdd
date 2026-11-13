<?php

namespace Lomkit\Osdd\Console\Phpunit;

use DOMDocument;

final readonly class XmlFileLayout
{
    private const string NEWLINE = "\n";

    private const string WINDOWS_NEWLINE = "\r\n";

    private function __construct(private string $contents) {}

    public static function of(string $contents): self
    {
        return new self($contents);
    }

    public function write(DOMDocument $document): string
    {
        $xml = (string) $document->saveXML();
        $rootName = $document->documentElement?->tagName;
        $writtenHead = $rootName === null ? null : self::head($xml, $rootName);
        $originalHead = $rootName === null ? null : self::head($this->contents, $rootName);

        if ($writtenHead === null || $originalHead === null || str_ends_with($originalHead, '/>')) {
            return $xml;
        }

        $body = substr($xml, strlen($writtenHead));

        if (! str_ends_with($this->contents, self::NEWLINE) && str_ends_with($body, self::NEWLINE)) {
            $body = substr($body, 0, -strlen(self::NEWLINE));
        }

        if (str_contains($this->contents, self::WINDOWS_NEWLINE)) {
            $body = str_replace(self::NEWLINE, self::WINDOWS_NEWLINE, $body);
        }

        return $originalHead.$body;
    }

    private static function head(string $xml, string $rootName): ?string
    {
        $matched = preg_match('/^.*?<'.preg_quote($rootName, '/').'(?=[\s>\/])(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', $xml, $matches);

        return $matched === 1 ? $matches[0] : null;
    }
}
