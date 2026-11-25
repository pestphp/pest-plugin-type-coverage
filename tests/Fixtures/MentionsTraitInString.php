<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * @internal
 */
final class MentionsTraitInString
{
    public function a($foo): string
    {
        return 'uses the trait Concern';
    }
}
