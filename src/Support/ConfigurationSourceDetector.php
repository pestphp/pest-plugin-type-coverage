<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

use PHPUnit\TextUI\CliArguments\Builder;
use PHPUnit\TextUI\CliArguments\XmlConfigurationFileFinder;
use PHPUnit\TextUI\Configuration\File;
use PHPUnit\TextUI\Configuration\FilterDirectory;
use PHPUnit\TextUI\XmlConfiguration\DefaultConfiguration;
use PHPUnit\TextUI\XmlConfiguration\Loader;
use Symfony\Component\Finder\Finder;

/**
 * @internal
 */
final class ConfigurationSourceDetector
{
    /**
     * Detects the "source" of the configuration.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    public static function detect(array $arguments = []): array
    {
        // Try to resolve configuration file directly from provided arguments
        $configurationFile = null;
        foreach ($arguments as $index => $arg) {
            if (str_starts_with($arg, '--configuration=')) {
                $configurationFile = substr($arg, strlen('--configuration='));
                break;
            }
            if ($arg === '--configuration') {
                $configurationFile = $arguments[$index + 1] ?? null;
                break;
            }
            if ($arg === '-c') {
                $configurationFile = $arguments[$index + 1] ?? null;
                break;
            }
        }

        if (! is_string($configurationFile)) {
            $cliConfiguration = (new Builder)->fromParameters($arguments);
            $configurationFile = (new XmlConfigurationFileFinder)->find($cliConfiguration);
        }
        $xmlConfiguration = DefaultConfiguration::create();

        if (is_string($configurationFile)) {
            $xmlConfiguration = (new Loader)->load($configurationFile);
        }

        $source = $xmlConfiguration->source();

        $includeDirectories = array_values(array_filter(array_map(
            fn (FilterDirectory $directory): string|false => realpath($directory->path()) ?: false,
            $source->includeDirectories()->asArray(),
        )));

        $includeFiles = array_values(array_filter(array_map(
            fn (File $file): string|false => realpath($file->path()) ?: false,
            $source->includeFiles()->asArray(),
        )));

        $excludeDirectories = array_values(array_filter(array_map(
            fn (FilterDirectory $directory): string|false => realpath($directory->path()) ?: false,
            $source->excludeDirectories()->asArray(),
        )));

        $excludeFiles = array_values(array_filter(array_map(
            fn (File $file): string|false => realpath($file->path()) ?: false,
            $source->excludeFiles()->asArray(),
        )));

        // Build the list of PHP files from included directories (if any)
        $filesFromDirectories = [];
        if ($includeDirectories !== []) {
            $finder = Finder::create()
                ->in($includeDirectories)
                ->name('*.php')
                ->notName('*.blade.php')
                ->files();

            $filesFromDirectories = array_map(
                static fn ($file): string => (string) realpath($file->getRealPath()),
                iterator_to_array($finder)
            );
        }

        // Merge with explicitly included files
        $allIncluded = array_values(array_unique(array_merge($filesFromDirectories, $includeFiles)));

        // Apply excludes
        $allIncluded = array_values(array_filter($allIncluded, static function (string $path) use ($excludeDirectories, $excludeFiles): bool {
            // Exclude explicit files
            if (in_array($path, $excludeFiles, true)) {
                return false;
            }

            // Exclude by directories
            foreach ($excludeDirectories as $excludeDir) {
                $prefix = rtrim($excludeDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                if (str_starts_with($path, $prefix)) {
                    return false;
                }
            }

            return true;
        }));

        return $allIncluded;
    }
}
