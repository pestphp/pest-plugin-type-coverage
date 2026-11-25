<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Not a trait itself, it only mentions the Concern trait in this docblock.
 *
 * @internal
 */
final class MentionsTrait
{
    public function a($foo): void
    {
        //
    }
}
