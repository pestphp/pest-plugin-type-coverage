<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class FileResolver
{
    /**
     * Resolves a mixed array of files and directories to a list of PHP files.
     *
     * @param  array<int, string>  $paths
     * @return array<int, string>
     */
    public static function resolve(array $paths): array
    {
        if ($paths === []) {
            return [];
        }

        $files = [];

        foreach ($paths as $path) {
            $realPath = realpath($path);

            if ($realPath === false || ! file_exists($realPath)) {
                continue;
            }

            if (is_file($realPath)) {
                if (pathinfo($realPath, PATHINFO_EXTENSION) === 'php') {
                    $files[] = $realPath;
                }
            } elseif (is_dir($realPath)) {
                $finder = Finder::create()->in($realPath)->name('*.php')->files();
                foreach ($finder as $file) {
                    $files[] = $file->getRealPath();
                }
            }
        }

        return array_unique($files);
    }
}
