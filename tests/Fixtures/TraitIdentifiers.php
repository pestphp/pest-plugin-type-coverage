<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * @internal
 */
final class TraitIdentifiers
{
    public const int TRAIT = 1;

    public static function trait($trait): int
    {
        return self::TRAIT;
    }

    public function call(): int
    {
        return self::trait(trait: TraitIdentifiersCase::trait);
    }
}

enum TraitIdentifiersCase
{
    case trait;
}
