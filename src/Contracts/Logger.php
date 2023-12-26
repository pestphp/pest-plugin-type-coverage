<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Contracts;

/**
 * @internal
 */
interface Logger
{
    /**
     * @param  array<int, string>  $uncoveredLines
     * @param  array<int, string>  $uncoveredLinesIgnored
     */
    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage): void;

    /**
     * Outputs the coverage report.
     */
    public function output(): void;
}
