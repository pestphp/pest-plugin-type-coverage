<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * @internal
 */
final class All
{
    use Concern;

    public const string FOO = 'foo';

    public const BAZ = 'baz';

    public $foo;

    public function a($foo, $bar)
    {
        //
    }
}
