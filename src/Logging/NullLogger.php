<?php

declare( strict_types=1 );

namespace Pest\TypeCoverage\Logging;

use Pest\TypeCoverage\Contracts\Logger;

/**
 * @internal
 *
 * @final
 */
class NullLogger implements Logger
{
    /**
     * @param string $outputPath
     * @param array<string, string|float|int|null>  $pluginSettings
     */
    public function __construct( string $outputPath, array $pluginSettings )
    {
        //
    }

    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage):void
    {
        //
    }

    public function output(): void
    {
        //
    }
}
