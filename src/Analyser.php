<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Closure;
use Pest\TypeCoverage\Support\Cache;

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
    public static function analyse(array $files, Closure $callback, Cache $cache): void
    {
        $testCase = new TestCaseForTypeCoverage('dummy');

        foreach ($files as $file) {

            [$errors, $ignored] = $cache->get($file, function () use ($file, $testCase) {
                $errors = $testCase->gatherAnalyserErrors([$file]);
                $ignored = $testCase->getIgnoredErrors();

                return [$errors, $ignored];
            });

            $testCase->resetIgnoredErrors();

            $callback(Result::fromPHPStanErrors($file, $errors, $ignored));
        }
    }
}
