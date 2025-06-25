<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Closure;
use Pest\TypeCoverage\Support\Cache;
use Pokio\Environment;

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
        $chunkOfFiles = Environment::supportsFork() ? array_chunk($files, Environment::maxProcesses()) : [$files];
        $promisses = [];

        foreach ($chunkOfFiles as $files) {
            $promisses[] = async(function () use ($cache, $files, $testCase, $onProcessedFile) {
                $results = [];

                foreach ($files as $file) {
                    [$file, $errors, $ignored] = $cache->get($file, function () use ($file, $testCase) {
                        $testCase->resetIgnoredErrors();

                        $errors = $testCase->gatherAnalyserErrors([$file]);
                        $ignored = $testCase->getIgnoredErrors();

                        return [$file, $errors, $ignored];
                    });

                    $result = Result::fromPHPStanErrors($file, $errors, $ignored);

                    $onProcessedFile($result);

                    $results[] = $result;
                }

                return $results;
            });
        }

        foreach (await($promisses) as $results) {
            foreach ($results as $result) {
                $postProcessedFile($result);
            }
        }
    }
}
