<?php

use Pest\TypeCoverage\Support\Cache;
use PHPStan\Analyser\Error;

beforeEach(function () {
    $this->cache = Cache::instance();
    $this->cache->flush();
});

afterEach(function () {
    $this->cache->flush();
});

test('it persists and retrieves cached results', function () {
    $file = __DIR__.'/../Fixtures/All.php';
    $error = new Error('Test error', $file, 10);

    $this->cache->persist($file, [$file, [$error], []]);

    expect($this->cache->has($file))->toBeTrue()
        ->and($this->cache->get($file))->toBeArray()
        ->and($this->cache->get($file)[0])->toBe($file)
        ->and($this->cache->get($file)[1])->toHaveCount(1)
        ->and($this->cache->get($file)[1][0])->toBeInstanceOf(Error::class)
        ->and($this->cache->get($file)[1][0]->getMessage())->toBe('Test error');
});

test('it persists and retrieves errors containing PhpParser node metadata', function () {
    $file = __DIR__.'/../Fixtures/All.php';
    $nodeName = new PhpParser\Node\Name('Tests\Fixtures\All');
    $error = new Error('Type mismatch', $file, 10, metadata: ['type' => $nodeName]);

    $this->cache->persist($file, [$file, [$error], []]);

    $cached = $this->cache->get($file);

    expect($cached[1])->toHaveCount(1)
        ->and($cached[1][0])->toBeInstanceOf(Error::class)
        ->and($cached[1][0]->getMessage())->toBe('Type mismatch');
});
