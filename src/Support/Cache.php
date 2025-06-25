<?php

declare(strict_types=1);

namespace Pest\TypeCoverage\Support;

/**
 * @internal
 */
final class Cache
{
    /**
     * The cache version.
     */
    private const string CACHE_VERSION = 'v1';

    /**
     * The cache instance.
     */
    public static function instance(): self
    {
        return new self;
    }

    /**
     * Gets the cache contents.
     *
     * @param  callable(): array  $callback
     * @return array<int, string>
     */
    public function get(string $file, callable $callback): array
    {
        $fileHash = @md5_file($file);
        if ($fileHash === false) {
            return $callback();
        }

        $items = $this->all();

        if (array_key_exists($fileHash, $items)) {
            return $items[$fileHash];
        }

        $values = $callback();

        $this->persist($fileHash, $values);

        return $values;
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
            .'temp'
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

            $cache = @include $this->file();

            return is_array($cache) ? $cache : [];
        });
    }

    /**
     * Persists the cache contents.
     */
    private function persist(string $key, array $values): void
    {
        $dirPath = dirname($this->file());
        if (! is_dir($dirPath)) {
            if (! @mkdir($dirPath, 0777, true)) {
                return;
            }
            @chmod($dirPath, 0777);
        }

        $this->withinLock(function () use ($key, $values) {
            $filePath = $this->file();
            $cache = [];

            if (is_file($filePath)) {
                $existingCache = @include $filePath;
                if (is_array($existingCache)) {
                    $cache = $existingCache;
                }
            }

            $cache[$key] = $values;

            $content = '<?php return '.var_export($cache, true).';';

            if (@file_put_contents($filePath, $content) !== false) {
                @chmod($filePath, 0666);
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
            @mkdir($dirPath, 0777, true);
            @chmod($dirPath, 0777);
        }

        if (! is_file($lockPath)) {
            @touch($lockPath);
            @chmod($lockPath, 0666);
        }

        $lock = @fopen($lockPath, 'c+');
        if ($lock === false) {
            return $callback();
        }

        $attempts = 0;
        while (! @flock($lock, LOCK_EX | LOCK_NB) && $attempts < 100) {
            usleep(1000);
            $attempts++;
        }

        if ($attempts >= 100) {
            @fclose($lock);

            return $callback();
        }

        try {
            return $callback();
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }
}
