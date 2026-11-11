<?php

namespace Lomkit\Osdd\Composer;

final class ComposerPackageOrder
{
    private const string PLATFORM_PACKAGE = '{^(?:php(?:-64bit|-ipv6|-zts|-debug)?|hhvm|(?:ext|lib)-[a-z0-9](?:[_.-]?[a-z0-9]+)*|composer(?:-(?:plugin|runtime)-api)?)$}iD';

    private const array PLATFORM_RANKS = [
        '/^php/' => '0-',
        '/^hhvm/' => '1-',
        '/^ext/' => '2-',
        '/^lib/' => '3-',
    ];

    private const string OTHER_PLATFORM_RANK = '4-';

    private const string PACKAGE_RANK = '5-';

    /**
     * Sort package links the way Composer does when config.sort-packages is on.
     *
     * @param  array<string, mixed>  $packages
     * @return array<string, mixed>
     */
    public static function sort(array $packages): array
    {
        uksort($packages, fn (string $a, string $b): int => strnatcmp(self::sortKey($a), self::sortKey($b)));

        return $packages;
    }

    private static function sortKey(string $package): string
    {
        if (preg_match(self::PLATFORM_PACKAGE, $package) !== 1) {
            return self::PACKAGE_RANK.$package;
        }

        foreach (self::PLATFORM_RANKS as $pattern => $rank) {
            if (preg_match($pattern, $package) === 1) {
                return $rank.$package;
            }
        }

        return self::OTHER_PLATFORM_RANK.$package;
    }
}
