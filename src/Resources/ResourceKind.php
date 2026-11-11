<?php

namespace Lomkit\Osdd\Resources;

enum ResourceKind: string
{
    case Views = 'views';
    case Translations = 'translations';
    case Components = 'components';

    public function helper(): string
    {
        return match ($this) {
            self::Views => 'withViews',
            self::Translations => 'withTranslations',
            self::Components => 'withComponents',
        };
    }

    public function identifier(): string
    {
        return match ($this) {
            self::Views, self::Translations => 'namespace',
            self::Components => 'prefix',
        };
    }
}
