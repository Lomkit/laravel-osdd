<?php

namespace Lomkit\Osdd\Resources;

enum ResourceKind: string
{
    case Views = 'views';
    case Translations = 'translations';
    case Components = 'components';
    case Livewire = 'livewire';

    public function helper(): string
    {
        return match ($this) {
            self::Views => 'withViews',
            self::Translations => 'withTranslations',
            self::Components => 'withComponents',
            self::Livewire => 'withLivewire',
        };
    }

    public function identifier(): string
    {
        return match ($this) {
            self::Views, self::Translations, self::Livewire => 'namespace',
            self::Components => 'prefix',
        };
    }
}
