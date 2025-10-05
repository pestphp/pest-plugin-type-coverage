<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Pest\Contracts\Plugins\HandlesOriginalArguments;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Shard;
use Pest\Support\View;
use Pest\TestSuite;
use Pest\TypeCoverage\Contracts\Logger;
use Pest\TypeCoverage\Logging\JsonLogger;
use Pest\TypeCoverage\Logging\NullLogger;
use Pest\TypeCoverage\Support\Cache;
use Pest\TypeCoverage\Support\ConfigurationSourceDetector;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

use function Termwind\render;
use function Termwind\renderUsing;
use function Termwind\terminal;

/**
 * @internal
 *
 * @final
 */
class Plugin implements HandlesOriginalArguments
{
    use HandleArguments;

    /**
     * The minimum coverage.
     */
    private float $coverageMin = 0.0;

    /**
     * The logger used to output type coverage to a file.
     */
    private Logger $coverageLogger;

    /**
     * Whether to use compact output.
     */
    private bool $compact = false;

    /**
     * Creates a new Plugin instance.
     */
    public function __construct(
        private readonly OutputInterface $output,
        private readonly Cache $cache,
    ) {
        $this->coverageLogger = new NullLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function handleOriginalArguments(array $arguments): void
    {
        if (! $this->hasArgument('--type-coverage', $arguments)) {
            return;
        }

        if ($this->hasArgument('--no-cache', $arguments)) {
            $this->cache->flush();
        }

        $startTime = microtime(true);

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--min')) {
                // grab the value of the --min argument
                $this->coverageMin = (float) explode('=', $argument)[1];
            }

            if (str_starts_with($argument, '--memory-limit')) {
                $memoryLimit = explode('=', $argument)[1] ?? '';

                if (preg_match('/^-?\d+[kMG]?$/', $memoryLimit) !== 1) {
                    View::render('components.badge', [
                        'type' => 'ERROR',
                        'content' => 'Invalid memory limit: '.$memoryLimit,
                    ]);

                    $this->exit(1);
                }

                if (ini_set('memory_limit', $memoryLimit) === false) {
                    View::render('components.badge', [
                        'type' => 'ERROR',
                        'content' => 'Failed to set memory limit: '.$memoryLimit,
                    ]);

                    $this->exit(1);
                }
            }

            if (str_starts_with($argument, '--type-coverage-json')) {
                $outputPath = explode('=', $argument)[1] ?? null;

                if ($outputPath === null) {
                    View::render('components.badge', [
                        'type' => 'ERROR',
                        'content' => 'No output path provided for [--type-coverage-json].',
                    ]);

                    $this->exit(1);
                }

                $this->coverageLogger = new JsonLogger(explode('=', $argument)[1], $this->coverageMin);
            }

            if (str_starts_with($argument, '--compact')) {
                $this->compact = true;
            }
        }

        $configArg = current(array_filter($arguments, fn ($arg) => str_starts_with($arg, '--configuration=')));
        $source = ConfigurationSourceDetector::detect($configArg ? [$configArg] : []);

        if ($source === []) {
            View::render('components.badge', [
                'type' => 'ERROR',
                'content' => 'No source section found. Did you forget to add a `source` section to your `phpunit.xml` file?',
            ]);

            $this->exit(1);
        }

        $files = Finder::create()
            ->in($source)
            ->name('*.php')
            ->notName('*.blade.php')
            ->files();

        $files = array_filter(
            iterator_to_array($files),
            fn (string $file): bool => ! str_contains(file_get_contents($file), 'trait '),
        );

        $totals = [];

        $this->output->writeln(['']);

        $terminalWidth = terminal()->width();

        $input = new ArgvInput($arguments);

        $total = 1;
        $index = 1;

        if ($input->hasParameterOption('--shard')) {
            ['index' => $index, 'total' => $total] = Shard::getShard($input);
        }

        if ($total > 1) {
            $files = array_filter($files, static function ($file) use ($index, $total): bool {
                return (crc32($file->getRealPath()) % $total) === ($index - 1);
            });
        }

        Analyser::analyse(
            array_keys($files),
            function (Result $result) use (&$totals): void {
                $path = str_replace(TestSuite::getInstance()->rootPath.DIRECTORY_SEPARATOR, '', $result->file);
                $uncoveredLines = [];
                $uncoveredLinesIgnored = [];

                $errors = $result->errors;
                $errorsIgnored = $result->errorsIgnored;

                usort($errors, static fn (Error $a, Error $b): int => $a->line <=> $b->line);
                usort($errorsIgnored, static fn (Error $a, Error $b): int => $a->line <=> $b->line);

                foreach ($errors as $error) {
                    $uncoveredLines[] = $error->getShortType().$error->line;
                }
                foreach ($errorsIgnored as $error) {
                    $uncoveredLinesIgnored[] = $error->getShortType().$error->line;
                }

                $this->coverageLogger->append($path, $uncoveredLines, $uncoveredLinesIgnored, $result->totalCoverage);
                $totals[] = $result->totalCoverage;
            },
            function (Result $result) use ($terminalWidth): void {
                $path = str_replace(TestSuite::getInstance()->rootPath.'/', '', $result->file);

                $truncateAt = max(1, $terminalWidth - 12);

                $uncoveredLines = [];
                $uncoveredLinesIgnored = [];

                $errors = $result->errors;
                $errorsIgnored = $result->errorsIgnored;

                usort($errors, static fn (Error $a, Error $b): int => $a->line <=> $b->line);
                usort($errorsIgnored, static fn (Error $a, Error $b): int => $a->line <=> $b->line);

                foreach ($errors as $error) {
                    $uncoveredLines[] = $error->getShortType().$error->line;
                }
                foreach ($errorsIgnored as $error) {
                    $uncoveredLinesIgnored[] = $error->getShortType().$error->line;
                }

                $color = $uncoveredLines === [] ? 'green' : 'yellow';

                $uncoveredLines = implode(', ', $uncoveredLines);
                $uncoveredLinesIgnored = implode(', ', $uncoveredLinesIgnored);

                if ($uncoveredLinesIgnored !== '') {
                    $uncoveredLinesIgnored = '<span class="text-gray">'.$uncoveredLinesIgnored.'</span>';
                    if ($uncoveredLines !== '') {
                        $uncoveredLinesIgnored = ' '.$uncoveredLinesIgnored;
                    }
                }

                $percentage = $result->totalCoverage;

                if ($this->compact === true && $percentage === 100) {
                    return;
                }

                renderUsing($this->output);
                render(<<<HTML
                <div class="flex mx-2">
                    <span class="truncate-{$truncateAt}">{$path}</span>
                    <span class="flex-1 content-repeat-[.] text-gray mx-1"></span>
                    <span class="text-{$color}">$uncoveredLines{$uncoveredLinesIgnored} {$percentage}%</span>
                </div>
                HTML);
            },
            $this->cache,
        );

        $coverage = array_sum($totals) / count($totals);

        $this->coverageLogger->output();

        $exitCode = (int) ($coverage < $this->coverageMin);

        $duration = number_format(microtime(true) - $startTime, 2, '.', '');

        if ($exitCode === 1) {
            View::render('components.badge', [
                'type' => 'ERROR',
                'content' => 'Type coverage below expected: '.number_format($this->coverageMin, 1).'%, currently '.number_format(floor($coverage * 10) / 10, 1).'%',
            ]);
        } else {
            $totalCoverageAsString = $coverage === 0
                ? '0.0'
                : number_format((float) $coverage, 1, '.', '');

            render(<<<HTML
                <div class="mx-2">
                    <hr class="text-gray" />
                    <div class="w-full text-right">
                        <span class="ml-1 font-bold"><span class="text-gray">({$duration}s)</span> Total: {$totalCoverageAsString} %</span>
                    </div>
                </div>
            HTML);

            $this->output->writeln(['']);
        }

        $this->exit($exitCode);
    }

    /**
     * Exits the process with the given code.
     */
    public function exit(int $code): never
    {
        exit($code);
    }
}
