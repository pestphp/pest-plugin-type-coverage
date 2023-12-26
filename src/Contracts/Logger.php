<?php

declare( strict_types=1 );

namespace Pest\TypeCoverage\Contracts;

/**
 * @internal
 *
 * @final
 */
interface Logger
{
    /**
     * @param string $outputPath
     * @param array<string, string|float|int|null>  $pluginSettings
     */
    public function __construct( string $outputPath, array $pluginSettings );

    /**
     * @param  array<int, string>  $uncoveredLines
     * @param  array<int, string>  $uncoveredLinesIgnored
     */
    public function append(string $path, array $uncoveredLines, array $uncoveredLinesIgnored, float $percentage):void;

    public function output():void;
}
