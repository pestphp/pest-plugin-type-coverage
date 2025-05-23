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
    public static function analyse(array $files, Closure $postProcessedFile, Closure $onProcessedFile, Cache $cache): void
    {
        $testCase = new TestCaseForTypeCoverage('dummy');
        $chunkOfFiles = array_chunk($files, 100);
        foreach ($chunkOfFiles as $files) {
            $promisses = [];

            foreach ($files as $file) {
                $promisses[] = async(function () use ($cache, $file, $testCase) {
                    return $cache->get($file, function () use ($file, $testCase) {
                        $testCase->resetIgnoredErrors();

                        $errors = $testCase->gatherAnalyserErrors([$file]);
                        $ignored = $testCase->getIgnoredErrors();

                        return [$file, $errors, $ignored];
                    });
                })->then(function (array $result) use ($onProcessedFile) {
                    [$file, $errors, $ignored] = $result;

                    $result = Result::fromPHPStanErrors($file, $errors, $ignored);

                    $onProcessedFile($result);

                    return $result;
                });
            }

            foreach (await($promisses) as $result) {
                $postProcessedFile($result);
            }
        }
    }
}
