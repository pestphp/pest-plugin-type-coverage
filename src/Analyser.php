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

        $cache = self::getCache();

        $cacheIsModified = false;
        foreach ($files as $file) {
            $fileHash = sha1_file($file);
            if (array_key_exists($file, $cache) && $cache[$file]['hash'] === $fileHash) {
                $errors = $cache[$file]['errors'];
                $ignored = $cache[$file]['ignored'];
            } else {
                $errors = $testCase->gatherAnalyserErrors([$file]);
                $ignored = $testCase->getIgnoredErrors();
                $cache[$file] = [
                    'hash' => $fileHash,
                    'errors' => $errors,
                    'ignored' => $ignored,
                ];
                $cacheIsModified = true;
            }
            $testCase->resetIgnoredErrors();

            $callback(Result::fromPHPStanErrors($file, $errors, $ignored));
        }
        $cache = self::clearRemovedFilesFromCache($cache);
        if ($cacheIsModified) {
            self::saveCache($cache);
        }
    }

    private static function getCache(): array
    {
        return is_file(self::getCacheFilepath())
            ? include self::getCacheFilepath()
            : [];
    }

    private static function saveCache(array $cache): void
    {
        file_put_contents(
            self::getCacheFilepath(),
            "<?php\nreturn ".var_export($cache, true).';',
        );
    }

    private static function getCacheFilepath(): string
    {
        return sys_get_temp_dir().'/pest_plugin_type_coverage_cache_'.md5(__DIR__).'.php';
    }

    private static function clearRemovedFilesFromCache(array $cache): array
    {
        foreach (array_keys($cache) as $file) {
            if (! is_file($file)) {
                unset($cache[$file]);
            }
        }

        return $cache;
    }
}
