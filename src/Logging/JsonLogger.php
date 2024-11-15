<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Logging;

use Pest\TypeCoverage\Contracts\Logger;

/**
 * @internal
 */
final class JsonLogger implements Logger
{
    /**
     * Creates a new Logger instance.
     *
     * @param  array<int, array<string, mixed>>  $logs
     */
    public function __construct(
        private readonly string $outputPath,
        private readonly float $coverageMin,
        private array $logs = [],
    ) {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage): void
    {
        $this->logs[] = [
            'file' => $path,
            'uncoveredLines' => $uncoveredLines,
            'uncoveredLinesIgnored' => $uncoveredLinesIgnored,
            'percentage' => $percentage,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function output(): void
    {
        $json = json_encode([
            'format' => 'pest',
            'coverage-min' => $this->coverageMin,
            'result' => $this->logs,
            'total' => round(array_sum(array_column($this->logs, 'percentage')) / count($this->logs), 2),
        ], JSON_THROW_ON_ERROR);
        file_put_contents($this->outputPath, $json);
    }
}
