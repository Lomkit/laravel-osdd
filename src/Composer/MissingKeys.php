<?php

namespace Lomkit\Osdd\Composer;

use stdClass;

final class MissingKeys
{
    /**
     * Copy into $target the keys of $defaults it does not have, at every depth; existing values win.
     */
    public static function copy(stdClass $defaults, stdClass $target): void
    {
        foreach (get_object_vars($defaults) as $key => $value) {
            if (! property_exists($target, $key)) {
                $target->{$key} = $value;

                continue;
            }

            if ($value instanceof stdClass && $target->{$key} instanceof stdClass) {
                self::copy($value, $target->{$key});
            }
        }
    }
}
