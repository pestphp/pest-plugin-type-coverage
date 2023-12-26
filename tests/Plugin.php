<?php

use Pest\TypeCoverage\Plugin;
use Symfony\Component\Console\Output\BufferedOutput;

test('output', function () {
    $output = new BufferedOutput();
    $plugin = new class($output) extends Plugin
    {
        public function exit(int $code): never
        {
            throw new Exception($code);
        }
    };

    expect(fn () => $plugin->handleArguments(['--type-coverage']))->toThrow(Exception::class, 0)
        ->and($output->fetch())->toContain(
            '.. 100%',
            '.. pr12 83',
            '.. pr12, pa14, pa14, rt14 0',
            '.. rt12 67',
            '.. pa12 83',
        );
});

test( 'it can output to json', function () {
    $output = new BufferedOutput();
    $plugin = new class( $output ) extends Plugin {
        public function exit( int $code ): never
        {
            throw new Exception( $code );
        }
    };

    expect( fn() => $plugin->handleArguments( [ '--type-coverage', '--type-coverage-json=test.json' ] ) )->toThrow( Exception::class, 0 );

    expect( __DIR__ . '/../test.json' )->toBeReadableFile();
    expect( file_get_contents( __DIR__ . '/../test.json' ) )->json()->toMatchArray( [
            "format" => "pest",
            'settings' => [
                'coverageMin' => 0,
            ],
            "result"   => [
                [
                    "file"                  => "src/PHPStanAnalyser.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/TestCaseForTypeCoverage.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Contracts/Logger.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Plugin.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Result.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Error.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Support/ConfigurationSourceDetector.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Analyser.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Logging/NullLogger.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "src/Logging/JsonLogger.php",
                    "uncoveredLines"        => [],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 100,
                ],
                [
                    "file"                  => "tests/Fixtures/Properties.php",
                    "uncoveredLines"        => ["pr12"],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 83,
                ],
                [
                    "file"                  => "tests/Fixtures/All.php",
                    "uncoveredLines"        => ["pr12", "pa14", "pa14", "rt14"],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 0,
                ],
                [
                    "file"                  => "tests/Fixtures/ReturnType.php",
                    "uncoveredLines"        => ["rt12"],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 67,
                ],
                [
                    "file"                  => "tests/Fixtures/Parameters.php",
                    "uncoveredLines"        => ["pa12"],
                    "uncoveredLinesIgnored" => [],
                    "percentage"            => 83,
                ],
            ],
            'total' => 88.07,
    ]);

    unlink( __DIR__ . '/../test.json' );
} );
