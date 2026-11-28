<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

/**
 * @internal
 */
final class TotalCoverage
{
    /**
     * Rounds the total coverage to the given precision, without rounding up to 100 while it is below 100.
     */
    public static function round(float $coverage, int $precision): float
    {
        $rounded = round($coverage, $precision);

        if ($coverage < 100 && $rounded >= 100) {
            return 100 - 10 ** -$precision;
        }

        return $rounded;
    }
}
