<?php

use Pest\TypeCoverage\Support\ConfigurationSourceDetector;

it('detects the source of the application', function () {
    $sources = ConfigurationSourceDetector::detect();

    expect($sources)->toBeArray()
        ->and($sources)->toContain(
            realpath(__DIR__.'/../../src/Plugin.php'),
            realpath(__DIR__.'/../../tests/Fixtures/All.php'),
        );
});

it('supports --configuration=<file> and includes single file', function () {
    $config = realpath(__DIR__.'/../Fixtures/phpunit.include.file.xml');
    $sources = ConfigurationSourceDetector::detect(['--configuration='.$config]);

    expect($sources)->toHaveCount(1)
        ->and($sources)->toContain(realpath(__DIR__.'/../Fixtures/All.php'));
});

it('supports --configuration <file> and excludes files', function () {
    $config = realpath(__DIR__.'/../Fixtures/phpunit.exclude.file.xml');
    $sources = ConfigurationSourceDetector::detect(['--configuration', $config]);

    expect($sources)->not->toContain(
        realpath(__DIR__.'/../Fixtures/Parameters.php')
    )->and($sources)->not->toBeEmpty();
});

it('supports -c <file>', function () {
    $config = realpath(__DIR__.'/../Fixtures/phpunit.include.file.xml');
    $sourcesShort = ConfigurationSourceDetector::detect(['-c', $config]);

    expect($sourcesShort)->toHaveCount(1)
        ->and($sourcesShort)->toContain(realpath(__DIR__.'/../Fixtures/All.php'));
});
