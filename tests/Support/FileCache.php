<?php

use Pest\TypeCoverage\Support\FileCache;

beforeEach(fn () => (new FileCache)->flush());

afterEach(fn () => (new FileCache)->flush());

it('keeps the cache contents in memory after the first read', function () {
    $file = realpath(__DIR__.'/../Fixtures/All.php');

    (new FileCache)->persist($file, [$file, [], []]);

    $cache = new FileCache;
    $cache->has($file);

    unlink(__DIR__.'/../../.temp/v3.php');

    expect($cache->has($file))->toBeTrue()
        ->and($cache->get($file))->toBe([$file, [], []]);
});

it('forgets entries after a flush', function () {
    $file = realpath(__DIR__.'/../Fixtures/All.php');

    (new FileCache)->persist($file, [$file, [], []]);

    $cache = new FileCache;
    $cache->has($file);

    $cache->flush();

    expect($cache->has($file))->toBeFalse();
});

it('reads entries persisted after the first read', function () {
    $file = realpath(__DIR__.'/../Fixtures/All.php');

    $cache = new FileCache;
    $cache->has($file);

    $cache->persist($file, [$file, [], []]);

    expect($cache->has($file))->toBeTrue()
        ->and($cache->get($file))->toBe([$file, [], []]);
});

it('persists several files with a single write', function () {
    $first = realpath(__DIR__.'/../Fixtures/All.php');
    $second = realpath(__DIR__.'/../Fixtures/ReturnType.php');

    (new FileCache)->persistMany([
        $first => [$first, [], []],
        $second => [$second, [], []],
    ]);

    $cache = new FileCache;

    expect($cache->has($first))->toBeTrue()
        ->and($cache->get($first))->toBe([$first, [], []])
        ->and($cache->has($second))->toBeTrue()
        ->and($cache->get($second))->toBe([$second, [], []]);
});

it('keeps the entries already in the cache file when persisting several files', function () {
    $first = realpath(__DIR__.'/../Fixtures/All.php');
    $second = realpath(__DIR__.'/../Fixtures/ReturnType.php');

    (new FileCache)->persist($first, [$first, [], []]);

    (new FileCache)->persistMany([$second => [$second, [], []]]);

    $cache = new FileCache;

    expect($cache->has($first))->toBeTrue()
        ->and($cache->has($second))->toBeTrue();
});
