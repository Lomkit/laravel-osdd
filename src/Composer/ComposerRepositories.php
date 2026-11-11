<?php

namespace Lomkit\Osdd\Composer;

use stdClass;

final readonly class ComposerRepositories
{
    private const string KEY = 'repositories';

    public function __construct(private ComposerJson $json) {}

    public function addPath(PathRepository $wanted): void
    {
        if ($this->keysOf($wanted) !== []) {
            return;
        }

        $repositories = $this->json->find(self::KEY) ?? [];

        if ($repositories instanceof stdClass) {
            $repositories->{$wanted->url} = $wanted->toObject();

            return;
        }

        $this->json->set(self::KEY, [...(is_array($repositories) ? $repositories : []), $wanted->toObject()]);
    }

    public function removePath(PathRepository $unwanted): void
    {
        $repositories = $this->json->find(self::KEY);
        $keys = $this->keysOf($unwanted, PathRepository::TYPE);

        if ($repositories instanceof stdClass) {
            foreach ($keys as $key) {
                unset($repositories->{$key});
            }

            return;
        }

        if (is_array($repositories) && $keys !== []) {
            $this->json->set(self::KEY, array_values(array_diff_key($repositories, array_flip($keys))));
        }
    }

    /**
     * @return list<PathRepository>
     */
    public function paths(): array
    {
        $pathRepositories = [];

        foreach ($this->entries() as $repository) {
            if (($repository->type ?? null) === PathRepository::TYPE && is_string($repository->url ?? null)) {
                $pathRepositories[] = PathRepository::fromUrl($this->directory(), $repository->url);
            }
        }

        return $pathRepositories;
    }

    /**
     * The keys of the entries pointing at the same directories as the repository, of the given type or of any type.
     *
     * @return list<array-key>
     */
    private function keysOf(PathRepository $repository, ?string $type = null): array
    {
        $keys = [];

        foreach ($this->entries() as $key => $entry) {
            if (($type === null || ($entry->type ?? null) === $type) && is_string($entry->url ?? null) && PathRepository::fromUrl($this->directory(), $entry->url)->isSameAs($repository)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @return array<array-key, stdClass>
     */
    private function entries(): array
    {
        $repositories = $this->json->find(self::KEY);

        return array_filter(
            is_array($repositories) || $repositories instanceof stdClass ? (array) $repositories : [],
            fn (mixed $repository): bool => $repository instanceof stdClass,
        );
    }

    private function directory(): string
    {
        return dirname($this->json->path);
    }
}
