<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

use LogicException;
use Pest\TypeCoverage\Contracts\Cache;

/**
 * @internal
 */
final class NullCache implements Cache
{
    /**
     * {@inheritdoc}
     */
    public function has(string $file): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): array
    {
        throw new LogicException('No cache found for the file: '.$file);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(string $file, array $values): void
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        //
    }
}
