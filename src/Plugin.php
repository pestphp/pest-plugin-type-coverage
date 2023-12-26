<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Support\View;
use Pest\TestSuite;
use Pest\TypeCoverage\Logging\JsonLogger;
use Pest\TypeCoverage\Logging\Logger;
use Pest\TypeCoverage\Support\ConfigurationSourceDetector;
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
class Plugin implements HandlesArguments
{
    use HandleArguments;

    /**
     * The minimum coverage.
     */
    private float $coverageMin = 0.0;

    /**
     * The logger used to output type coverage to a file.
     */
    private ?Logger $coverageLogger = null;

    /**
     * Creates a new Plugin instance.
     */
    public function __construct(
        private readonly OutputInterface $output
    ) {
        // ..
    }

    /**
     * {@inheritdoc}
     */
    public function handleArguments(array $arguments): array
    {
        if (! $this->hasArgument('--type-coverage', $arguments) && ! $this->hasArgument('--type-coverage-json', $arguments)) {
            return $arguments;
        }

        foreach ($arguments as $argument) {
            if (str_starts_with($argument, '--min')) {
                // grab the value of the --min argument
                $this->coverageMin = (float) explode('=', $argument)[1];
            }

            if (str_starts_with($argument, '--type-coverage-json')) {
                // grab the value of the --type-coverage-json argument
                $this->coverageLogger = new JsonLogger(explode('=', $argument)[1], ['coverageMin' => $this->coverageMin]);
            }
        }

        $source = ConfigurationSourceDetector::detect();

        if ($source === []) {
            View::render('components.badge', [
                'type' => 'ERROR',
                'content' => 'No source section found. Did you forget to add a `source` section to your `phpunit.xml` file?',
            ]);

            $this->exit(1);
        }

        $files = Finder::create()->in($source)->name('*.php')->files();
        $totals = [];

        $this->output->writeln(['']);

        Analyser::analyse(
            array_keys(iterator_to_array($files)),
            function (Result $result) use (&$totals): void {
                $path = str_replace(TestSuite::getInstance()->rootPath.'/', '', $result->file);

                $truncateAt = max(1, terminal()->width() - 12);

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

                $this->coverageLogger?->append( $path, $uncoveredLines, $uncoveredLinesIgnored, $result->totalCoverage );

                $uncoveredLines = implode(', ', $uncoveredLines);
                $uncoveredLinesIgnored = implode(', ', $uncoveredLinesIgnored);
                // if there are uncovered lines, add a space before the ignored lines
                // but only if there are ignored lines
                if ($uncoveredLinesIgnored !== '') {
                    $uncoveredLinesIgnored = '<span class="text-gray">'.$uncoveredLinesIgnored.'</span>';
                    if ($uncoveredLines !== '') {
                        $uncoveredLinesIgnored = ' '.$uncoveredLinesIgnored;
                    }
                }

                $totals[] = $percentage = $result->totalCoverage;

                renderUsing($this->output);
                render(<<<HTML
                <div class="flex mx-2">
                    <span class="truncate-{$truncateAt}">{$path}</span>
                    <span class="flex-1 content-repeat-[.] text-gray mx-1"></span>
                    <span class="text-{$color}">$uncoveredLines{$uncoveredLinesIgnored} {$percentage}%</span>
                </div>
                HTML);
            },
        );

        $coverage = array_sum($totals) / count($totals);

        $this->coverageLogger?->output();

        $exitCode = (int) ($coverage < $this->coverageMin);

        if ($exitCode === 1) {
            View::render('components.badge', [
                'type' => 'ERROR',
                'content' => 'Type coverage below expected: '.number_format($coverage, 1).'%. Minimum: '.number_format($this->coverageMin, 1).'%',
            ]);
        } else {
            $totalCoverageAsString = $coverage === 0
                ? '0.0'
                : number_format((float) $coverage, 1, '.', '');

            render(<<<HTML
                <div class="mx-2">
                    <hr class="text-gray" />
                    <div class="w-full text-right">
                        <span class="ml-1 font-bold">Total: {$totalCoverageAsString} %</span>
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
