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
        $items = $this->all();

        if (array_key_exists(md5_file($file), $items)) {
            return $items[md5_file($file)];
        }

        $values = $callback();

        $this->persist(md5_file($file), $values);

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

            $cache = include $this->file();

            if (! is_array($cache)) {
                return [];
            }

            return $cache;
        });
    }

    /**
     * Persists the cache contents.
     */
    private function persist(string $key, array $values): void
    {
        $cache = $this->all();

        $cache[$key] = $values;

        // ensure folder exists
        if (! is_dir(dirname($this->file()))) {
            mkdir(dirname($this->file()), 0755, true);
        }

        $this->withinLock(
            fn () => file_put_contents($this->file(), '<?php return '.var_export($cache, true).';')
        );
    }

    /**
     * Executes the callback within a lock.
     */
    private function withinLock(callable $callback): mixed
    {
        if (! is_file($this->file())) {
            return $callback();
        }

        $lock = fopen($this->file(), 'c+');

        if ($lock === false) {
            return $callback();
        }

        // wait for the lock
        while (! flock($lock, LOCK_EX | LOCK_NB)) {
            usleep(1);
        }

        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
