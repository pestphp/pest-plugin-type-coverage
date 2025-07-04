<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

use LogicException;
use PHPStan\Analyser\Error;

/**
 * @internal
 */
final class Cache
{
    /**
     * The cache version.
     */
    private const string CACHE_VERSION = 'v3';

    /**
     * The cache instance.
     */
    public static function instance(): self
    {
        return new self;
    }

    /**
     * Checks if the cache contains the given file.
     */
    public function has(string $file): bool
    {
        $fileHash = md5_file($file);

        if ($fileHash === false) {
            return false;
        }

        $items = $this->all();

        return array_key_exists($fileHash, $items);
    }

    /**
     * Gets the cached contents for the given file.
     *
     * @return array{0: string, 1: array<int, Error>, 2: array<int, Error>}
     *
     * @throws LogicException
     */
    public function get(string $file): array
    {
        $fileHash = md5_file($file);

        if ($fileHash === false) {
            throw new LogicException('Failed to compute the hash for the file: '.$file);
        }

        $items = $this->all();

        return $items[$fileHash] ?? throw new LogicException('No cache found for the file: '.$file);
    }

    /**
     * Flushes all the cache contents.
     */
    public function flush(): void
    {
        if (is_file($this->file())) {
            unlink($this->file());
        }
    }

    /**
     * Returns the cache file.
     */
    private function file(): string
    {
        return dirname(__DIR__, 2)
            .DIRECTORY_SEPARATOR
            .'.temp'
            .DIRECTORY_SEPARATOR
            .self::CACHE_VERSION
            .'.php';
    }

    /**
     * Gets all the cache contents.
     */
    private function all(): array
    {
        return $this->withinLock(function () {
            if (! is_file($this->file())) {
                return [];
            }

            $cache = include $this->file();

            return is_array($cache) ? $cache : [];
        });
    }

    /**
     * Persists the cache contents.
     */
    public function persist(string $file, array $values): void
    {
        $fileHash = md5_file($file);

        foreach ($values as $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof Error) {
                        (fn () => $this->canBeIgnored = null)->call($item);
                    }
                }
            }
        }

        $dirPath = dirname($this->file());
        if (! is_dir($dirPath)) {
            if (! mkdir($dirPath, 0755, true)) {
                return;
            }
            chmod($dirPath, 0755);
        }

        $this->withinLock(function () use ($fileHash, $values) {
            $filePath = $this->file();
            $cache = [];

            if (is_file($filePath)) {
                $existingCache = include $filePath;
                if (is_array($existingCache)) {
                    $cache = $existingCache;
                }
            }

            $cache[$fileHash] = $values;

            $content = '<?php return '.var_export($cache, true).';';

            if (file_put_contents($filePath, $content) !== false) {
                chmod($filePath, 0666);
            }

            return null;
        });
    }

    /**
     * Executes the callback within a lock.
     */
    private function withinLock(callable $callback): mixed
    {
        $filePath = $this->file();
        $lockPath = $filePath.'.lock';
        $dirPath = dirname($filePath);

        if (! is_dir($dirPath)) {
            mkdir($dirPath, 0755, true);
            chmod($dirPath, 0755);
        }

        if (! is_file($lockPath)) {
            touch($lockPath);
            chmod($lockPath, 0666);
        }

        $lock = fopen($lockPath, 'c+');
        if ($lock === false) {
            return $callback();
        }

        $attempts = 0;
        while (! flock($lock, LOCK_EX | LOCK_NB) && $attempts < 100) {
            usleep(1000);
            $attempts++;
        }

        if ($attempts >= 100) {
            fclose($lock);

            return $callback();
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
