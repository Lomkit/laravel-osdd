<?php

namespace Lomkit\Osdd\Scaffolding;

use Lomkit\Osdd\Console\Exceptions\UnknownLayerGeneratorException;

enum LayerGenerator: string
{
    case Migration = 'migration';
    case Model = 'model';
    case Factory = 'factory';
    case Seeder = 'seeder';
    case ServiceProvider = 'service-provider';
    case Test = 'test';
    case Controller = 'controller';
    case Policy = 'policy';
    case Routes = 'routes';

    /**
     * @return list<self>
     */
    public static function defaults(): array
    {
        return [self::Migration, self::Model, self::Factory, self::ServiceProvider, self::Test];
    }

    /**
     * Parse --generators values, each one a name or a comma-separated list of names.
     *
     * @param  array<array-key, mixed>  $values
     * @return list<self>
     *
     * @throws UnknownLayerGeneratorException
     */
    public static function parse(array $values): array
    {
        $generators = [];

        foreach ($values as $value) {
            foreach (explode(',', (string) (is_scalar($value) ? $value : '')) as $name) {
                $name = trim($name);

                if ($name === '') {
                    continue;
                }

                $generator = self::tryFrom($name) ?? throw new UnknownLayerGeneratorException($name);

                if (! in_array($generator, $generators, true)) {
                    $generators[] = $generator;
                }
            }
        }

        return $generators;
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $generator): string => $generator->value, self::cases());
    }
}
