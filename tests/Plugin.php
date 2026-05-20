<?php

use Pest\TypeCoverage\Plugin;
use Symfony\Component\Console\Output\BufferedOutput;

test('output', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 87',
            '.. co14, pr16, pa18, pa18, rt18 12',
            '.. co14 87',
            '.. rt12 75',
            '.. pa12 87',
        );
});

test('output with --compact', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleOriginalArguments(['--type-coverage', '--compact']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. pr12 87',
            '.. co14, pr16, pa18, pa18, rt18 12',
            '.. co14 87',
            '.. rt12 75',
            '.. pa12 87',
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

test('extracts file arguments correctly', function () {
    $plugin = new class(new BufferedOutput) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $arguments = ['--type-coverage', '--', 'file1.php', 'dir1/', 'file2.php'];
    expect($plugin->extractFileArguments($arguments))
        ->toBe(['file1.php', 'dir1/', 'file2.php']);

    $arguments = ['--type-coverage', '--min=80'];
    expect($plugin->extractFileArguments($arguments))->toBe([]);

    $arguments = ['--type-coverage', '--', 'file1.php', '--some-option'];
    expect($plugin->extractFileArguments($arguments))->toBe(['file1.php']);
});

test('handles specific file arguments', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $testFile = __DIR__.'/Fixtures/TestFiles/SimpleClass.php';
    $arguments = ['--type-coverage', '--', $testFile];

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, 0);

    $output = $output->fetch();
    expect($output)->toContain('SimpleClass.php')
        ->and($output)->toContain('100%')
        ->and($output)->toContain('Total: 100.0 %');
});

test('handles directory arguments', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $testDir = __DIR__.'/Fixtures/TestFiles';
    $arguments = ['--type-coverage', '--', $testDir];

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, 0);

    $output = $output->fetch();
    expect($output)->toContain('SimpleClass.php')
        ->and($output)->toContain('MissingTypes.php')
        ->and($output)->toContain('NestedClass.php')
        ->and($output)->not->toContain('readme.txt');
});

test('handles mixed file and directory arguments', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $testFile = __DIR__.'/Fixtures/TestFiles/SimpleClass.php';
    $testSubDir = __DIR__.'/Fixtures/TestFiles/SubDir';
    $arguments = ['--type-coverage', '--', $testFile, $testSubDir];

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, 0);

    $output = $output->fetch();
    expect($output)->toContain('SimpleClass.php')
        ->and($output)->toContain('NestedClass.php')
        ->and($output)->not->toContain('MissingTypes.php');
});

test('shows error when no PHP files found in specified paths', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $emptyDir = __DIR__.'/Fixtures/TestFiles/EmptyDir';
    $arguments = ['--type-coverage', '--', $emptyDir];

    $initialLevel = ob_get_level();

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, '1');

    while (ob_get_level() > $initialLevel) {
        ob_end_clean();
    }
});

test('works with non-existent files and valid files mixed', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    $validFile = __DIR__.'/Fixtures/TestFiles/SimpleClass.php';
    $invalidFile = __DIR__.'/Fixtures/TestFiles/NonExistent.php';
    $arguments = ['--type-coverage', '--', $validFile, $invalidFile];

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, 0);

    $output = $output->fetch();
    expect($output)->toContain('SimpleClass.php')
        ->and($output)->toContain('Total: 100.0 %');
});

test('combines file arguments with other options', function () {
    $output = new BufferedOutput;
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception((string) $code);
        }
    };

    $testFile = __DIR__.'/Fixtures/TestFiles/MissingTypes.php';
    $arguments = ['--type-coverage', '--compact', '--min=90.0', '--', $testFile];

    $initialLevel = ob_get_level();

    expect(fn () => $plugin->handleOriginalArguments($arguments))->toThrow(Exception::class, '1');

    while (ob_get_level() > $initialLevel) {
        ob_end_clean();
    }

    $output = $output->fetch();
    expect($output)->toContain('MissingTypes.php')
        ->and($output)->toContain('50%');
});
