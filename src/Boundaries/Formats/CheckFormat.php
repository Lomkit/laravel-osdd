<?php

namespace Lomkit\Osdd\Boundaries\Formats;

enum CheckFormat: string
{
    case Text = 'text';
    case Json = 'json';
    case Github = 'github';

    public function renderer(): BoundaryReportFormat
    {
        return match ($this) {
            self::Text => new TextBoundaryReport,
            self::Json => new JsonBoundaryReport,
            self::Github => new GithubAnnotations,
        };
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $format): string => $format->value, self::cases());
    }
}
