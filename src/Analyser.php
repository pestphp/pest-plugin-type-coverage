<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Closure;

/**
 * @internal
 */
final class Analyser
{
    /**
     * Analyse the code's type coverage.
     *
     * @param  array<int, string>  $files
     * @param  \Closure(\Pest\TypeCoverage\Result): void  $callback
     */
    public static function analyse(array $files, Closure $callback): void
    {
        $testCase = new TestCaseForTypeCoverage('test');

        foreach ($files as $file) {
            $errors = $testCase->gatherAnalyserErrors([$file]);

            $callback(Result::fromPHPStanErrors($file, $errors));
        }
    }
}
