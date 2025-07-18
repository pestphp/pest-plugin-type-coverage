<?php

use Pest\TypeCoverage\Plugin;
use Pest\TypeCoverage\Support\Cache;
use Symfony\Component\Console\Output\BufferedOutput;

beforeEach(fn () => pokio()->useSync());

test('output with `--no-cache`', function () {
    $output = new BufferedOutput;
    $plugin = new class($output, new Cache) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage', '--no-cache']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 75',
            '.. co16, pr18, pa20, pa20, rt20 0',
            '.. co14 75',
            '.. rt12 75',
            '.. pa12 75',
        );
});

test('output', function () {
    $output = new BufferedOutput;
    $plugin = new class($output, new Cache) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 75',
            '.. co16, pr18, pa20, pa20, rt20 0',
            '.. co14 75',
            '.. rt12 75',
            '.. pa12 75',
        );
});

test('output with --compact', function () {
    $output = new BufferedOutput;
    $plugin = new class($output, new Cache) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage', '--compact']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. pr12 75',
            '.. co16, pr18, pa20, pa20, rt20',
            '.. co14 75',
            '.. rt12 75',
            '.. pa12 75',
        )->not->toContain('.. 100%');
});

test('it can output to json', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage', '--type-coverage-json=test.json']))->toThrow(Exception::class, 0);

    expect(__DIR__.'/../test.json')->toBeReadableFile();

    expect(file_get_contents(__DIR__.'/../test.json'))->json()->toMatchArray([
        'format' => 'pest',
        'coverage-min' => 0,
        'result' => [
            [
                'file' => 'src/PHPStanAnalyser.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/TestCaseForTypeCoverage.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Contracts/Logger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Plugin.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Result.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Error.php',
                'uncoveredLines' => [
                    'co15',
                    'co17',
                    'co19',
                    'co21',
                ],
                'uncoveredLinesIgnored' => [],
                'percentage' => 75,
            ],
            [
                'file' => 'src/Support/ConfigurationSourceDetector.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Analyser.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Logging/NullLogger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'src/Logging/JsonLogger.php',
                'uncoveredLines' => [],
                'uncoveredLinesIgnored' => [],
                'percentage' => 100,
            ],
            [
                'file' => 'tests/Fixtures/Properties.php',
                'uncoveredLines' => ['pr12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
            [
                'file' => 'tests/Fixtures/All.php',
                'uncoveredLines' => [
                    'co14',
                    'pr16',
                    'pa18',
                    'pa18',
                    'rt18',
                ],
                'uncoveredLinesIgnored' => [],
                'percentage' => 12,
            ],
            [
                'file' => 'tests/Fixtures/Constants.php',
                'uncoveredLines' => ['co14'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
            [
                'file' => 'tests/Fixtures/ReturnType.php',
                'uncoveredLines' => ['rt12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 75,
            ],
            [
                'file' => 'tests/Fixtures/Parameters.php',
                'uncoveredLines' => ['pa12'],
                'uncoveredLinesIgnored' => [],
                'percentage' => 87,
            ],
        ],
        'total' => 88.2,
    ]);

    unlink(__DIR__.'/../test.json');
})->todo();
