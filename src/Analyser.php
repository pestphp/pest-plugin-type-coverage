<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Closure;
use Pest\TypeCoverage\Support\Cache;
use PHPStan\Analyser\Error;
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

        if (count($files) === 0) {
            return;
        }

        $filesTouched = [];

        foreach ($files as $file) {
            if ($cache->has($file)) {
                [$file, $errors, $ignored] = $cache->get($file);

                $result = Result::fromPHPStanErrors($file, $errors, $ignored);

                $postProcessedFile($result);
                $onProcessedFile($result);
            } else {
                $filesTouched[] = $file;
            }
        }

        unset($files);

        // next, if we don't have touched files, we can return early

        if (count($filesTouched) === 0) {
            return;
        }

        // if not, lets warm up the cache with the first file:

        $firstFile = array_shift($filesTouched);
        self::analyseChunks(
            [[$firstFile]],
            $testCase,
            $postProcessedFile,
            $onProcessedFile,
            $cache,
            false,
        );

        $maxProcesses = (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV']))
            ? (Environment::maxProcesses() / 3)
            : 1;

        $maxProcesses = max(1, $maxProcesses);

        $chunkOfFiles = array_fill(0, $maxProcesses, []);
        foreach (array_values($filesTouched) as $i => $file) {
            $chunkOfFiles[$i % $maxProcesses][] = $file;
        }

        $chunkOfFiles = array_values(
            array_filter($chunkOfFiles, static fn (array $chunk) => count($chunk) > 0),
        );

        self::analyseChunks(
            $chunkOfFiles,
            $testCase,
            $postProcessedFile,
            $onProcessedFile,
            $cache,
        );
    }

    /**
     * Analyse the chunks of files.
     */
    private static function analyseChunks(
        array $chunks,
        TestCaseForTypeCoverage $testCase,
        Closure $postProcessedFile,
        Closure $onProcessedFile,
        Cache $cache,
        bool $useAsync = true,
    ): void {
        $promises = [];

        if ($useAsync === false) {
            pokio()->useSync();
        } else {
            if (Environment::supportsFork() && ! isset($_ENV['__PEST_PLUGIN_ENV'])) {
                pokio()->useFork();
            }
        }

        foreach ($chunks as $files) {
            $promises[] = async(function () use ($cache, $files, $testCase, $onProcessedFile) {
                $testCase->resetIgnoredErrors();
                $results = [];

                $analyserErrors = $testCase->gatherAnalyserErrors($files);
                $analyserIgnored = $testCase->getIgnoredErrors();

                foreach ($files as $file) {
                    $errors = array_filter($analyserErrors, static fn (Error $error) => $error->getFile() === $file);
                    $ignored = array_filter($analyserIgnored, static fn (Error $error) => $error->getFile() === $file);

                    $errors = array_values($errors);
                    $ignored = array_values($ignored);

                    $cache->persist($file, [$file, $errors, $ignored]);

                    $result = Result::fromPHPStanErrors($file, $errors, $ignored);

                    $onProcessedFile($result);

                    $results[] = $result;
                }

                return $results;
            });
        }

        foreach (await($promises) as $results) {
            foreach ($results as $result) {
                $postProcessedFile($result);
            }
        }
    }
}
