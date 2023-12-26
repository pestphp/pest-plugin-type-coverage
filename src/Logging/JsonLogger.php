<?php

declare( strict_types=1 );

namespace Pest\TypeCoverage\Logging;

use Pest\TypeCoverage\Contracts\Logger;

/**
 * @internal
 *
 * @final
 */
class JsonLogger implements Logger
{
    private string $outputPath;

    /** @var array<string, string|float|int|null> */
    private array $pluginSettings;

    /** @var array<int, array<string, string|float|int|array<int,string>>> */
    private array $logs = [];

    public function __construct( string $outputPath, array $pluginSettings ) {
        $this->outputPath = $outputPath;
        $this->pluginSettings = $pluginSettings;
    }

    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage):void
    {
        $this->logs[] = [
            'file' => $path,
            'uncoveredLines' => $uncoveredLines,
            'uncoveredLinesIgnored' => $uncoveredLinesIgnored,
            'percentage' => $percentage,
        ];
    }

    public function output(): void
    {
        $json = json_encode( [
            'format'   => 'pest',
            'settings' => $this->pluginSettings,
            'result'     => $this->logs,
            'total'    => round( array_sum(array_column($this->logs, 'percentage')) / count($this->logs), 2 )
        ], JSON_THROW_ON_ERROR );
        file_put_contents($this->outputPath, $json);
    }
}
