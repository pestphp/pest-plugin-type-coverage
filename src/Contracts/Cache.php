<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Contracts;

use LogicException;
use PHPStan\Analyser\Error;

/**
 * @internal
 */
interface Cache
{
    /**
     * Checks if the cache contains the given file.
     */
    public function has(string $file): bool;

    /**
     * Gets the cached contents for the given file.
     *
     * @return array{0: string, 1: array<int, Error>, 2: array<int, Error>}
     *
     * @throws LogicException
     */
    public function get(string $file): array;

    /**
     * Persists the cache contents.
     *
     * @param  array{0: string, 1: array<int, Error>, 2: array<int, Error>}  $values
     */
    public function persist(string $file, array $values): void;

    /**
     * Flushes all the cache contents.
     */
    public function flush(): void;
}
