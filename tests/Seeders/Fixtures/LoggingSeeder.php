<?php

namespace Lomkit\Osdd\Tests\Seeders\Fixtures;

use Illuminate\Database\Seeder;

abstract class LoggingSeeder extends Seeder
{
    /**
     * @var list<class-string<LoggingSeeder>>
     */
    public static array $runs = [];

    public function run(): void
    {
        self::$runs[] = static::class;
    }
}
