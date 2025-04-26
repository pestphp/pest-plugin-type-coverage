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
     * @param  Closure(Result): void  $callback
     */
    public static function analyse(array $files, Closure $callback): void
    {
        $testCase = new TestCaseForTypeCoverage;

        foreach ($files as $file) {
            $errors = $testCase->gatherAnalyserErrors([$file]);
            $ignored = $testCase->getIgnoredErrors();
            $testCase->resetIgnoredErrors();

            $callback(Result::fromPHPStanErrors($file, $errors, $ignored));
        }
    }
}
