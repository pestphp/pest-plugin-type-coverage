<?php

use Pest\TypeCoverage\Support\FileResolver;

beforeEach(function () {
    $this->fixturesPath = __DIR__.'/../Fixtures/TestFiles';
});

test('resolves empty array when no paths provided', function () {
    expect(FileResolver::resolve([]))->toBe([]);
});

test('resolves single PHP file', function () {
    $phpFile = $this->fixturesPath.'/SimpleClass.php';

    $result = FileResolver::resolve([$phpFile]);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(realpath($phpFile));
});

test('ignores non-PHP files', function () {
    $phpFile = $this->fixturesPath.'/SimpleClass.php';
    $txtFile = $this->fixturesPath.'/readme.txt';

    $result = FileResolver::resolve([$phpFile, $txtFile]);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(realpath($phpFile));
});

test('resolves directory to PHP files', function () {
    $result = FileResolver::resolve([$this->fixturesPath]);

    expect($result)->toHaveCount(3)
        ->and(in_array(realpath($this->fixturesPath.'/SimpleClass.php'), $result))->toBeTrue()
        ->and(in_array(realpath($this->fixturesPath.'/MissingTypes.php'), $result))->toBeTrue()
        ->and(in_array(realpath($this->fixturesPath.'/SubDir/NestedClass.php'), $result))->toBeTrue();
});

test('resolves nested directories', function () {
    $result = FileResolver::resolve([$this->fixturesPath]);

    expect($result)->toHaveCount(3)
        ->and(in_array(realpath($this->fixturesPath.'/SimpleClass.php'), $result))->toBeTrue()
        ->and(in_array(realpath($this->fixturesPath.'/SubDir/NestedClass.php'), $result))->toBeTrue();
});

test('handles mixed files and directories', function () {
    $directFile = $this->fixturesPath.'/SimpleClass.php';
    $subDir = $this->fixturesPath.'/SubDir';

    $result = FileResolver::resolve([$directFile, $subDir]);

    expect($result)->toHaveCount(2)
        ->and(in_array(realpath($directFile), $result))->toBeTrue()
        ->and(in_array(realpath($this->fixturesPath.'/SubDir/NestedClass.php'), $result))->toBeTrue();
});

test('skips non-existent paths', function () {
    $existingFile = $this->fixturesPath.'/SimpleClass.php';
    $nonExistentFile = $this->fixturesPath.'/nonexistent.php';
    $nonExistentDir = $this->fixturesPath.'/nonexistent_dir';

    $result = FileResolver::resolve([$existingFile, $nonExistentFile, $nonExistentDir]);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBe(realpath($existingFile));
});

test('removes duplicate files', function () {
    $phpFile = $this->fixturesPath.'/SimpleClass.php';

    $result = FileResolver::resolve([$phpFile, $phpFile, $this->fixturesPath]);

    expect($result)->toHaveCount(3);
});

test('handles empty directory', function () {
    $emptyDir = $this->fixturesPath.'/EmptyDir';

    $result = FileResolver::resolve([$emptyDir]);

    expect($result)->toBe([]);
});

test('handles directory with no PHP files', function () {
    $emptyDir = $this->fixturesPath.'/EmptyDir';

    $result = FileResolver::resolve([$emptyDir]);

    expect($result)->toBe([]);
});
