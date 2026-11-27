<?php

use Pest\TypeCoverage\Support\TotalCoverage;

it('rounds the total coverage without rounding up to 100', function (float $coverage, int $precision, float $expected) {
    expect(TotalCoverage::round($coverage, $precision))->toBe($expected);
})->with([
    'rounds up' => [87.25, 1, 87.3],
    'rounds down' => [88.24, 1, 88.2],
    'full coverage' => [100.0, 1, 100.0],
    'no coverage' => [0.0, 1, 0.0],
    'just below the boundary' => [99.94, 1, 99.9],
    'one file at 75 out of 500' => [(499 * 100 + 75) / 500, 1, 99.9],
    'one file at 75 out of 2000' => [(1999 * 100 + 75) / 2000, 1, 99.9],
    'one file at 75 out of 5000' => [(4999 * 100 + 75) / 5000, 2, 99.99],
    'one file at 75 out of 20000' => [(19999 * 100 + 75) / 20000, 2, 99.99],
]);
