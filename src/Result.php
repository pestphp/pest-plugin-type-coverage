<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Pest\TestSuite;
use PHPStan\Analyser\Error as PHPStanError;

/**
 * @internal
 */
final class Result
{
    /**
     * Either the project supports constant types or not.
     */
    private static ?bool $supportsConstantTypes = null;

    /**
     * Creates a new result instance.
     *
     * @param  array<int, Error>  $errors
     * @param  array<int, Error>  $errorsIgnored
     */
    private function __construct(
        public readonly string $file,
        public readonly array $errors,
        public readonly array $errorsIgnored,
        public readonly int $propertyCoverage,
        public readonly int $paramCoverage,
        public readonly int $returnTypeCoverage,
        public readonly int $constantsCoverage,
        public readonly int $totalCoverage,
    ) {
        //
    }

    /**
     * Creates a new result instance from the given PHPStan errors.
     *
     * @param  array<int, PHPStanError>  $phpstanErrors
     * @param  array<int, PHPStanError>  $phpstanErrorsIgnored
     */
    public static function fromPHPStanErrors(string $file, array $phpstanErrors, array $phpstanErrorsIgnored): self
    {
        $filter = static fn (PHPStanError $error): bool => str_contains($error->getMessage(), 'property types')
            || str_contains($error->getMessage(), 'param types')
            || str_contains($error->getMessage(), 'return types')
            || (self::supportsConstantTypes() && str_contains($error->getMessage(), 'constant types'));

        $phpstanErrors = array_filter($phpstanErrors, $filter);
        $phpstanErrorsIgnored = array_filter($phpstanErrorsIgnored, $filter);

        $errors = array_map(
            static fn (PHPStanError $error): Error => Error::fromPHPStanError($error),
            $phpstanErrors,
        );

        $errorsIgnored = array_map(
            static fn (PHPStanError $error): Error => Error::fromPHPStanError($error),
            $phpstanErrorsIgnored,
        );

        $propertyCoverage = 100;
        $paramCoverage = 100;
        $returnTypeCoverage = 100;
        $constantsCoverage = 100;

        foreach ($phpstanErrors as $error) {
            if (str_contains($error->getMessage(), 'property types')) {
                $propertyCoverage = 0;
            }
            if (str_contains($error->getMessage(), 'param types')) {
                $paramCoverage = 0;
            }
            if (str_contains($error->getMessage(), 'return types')) {
                $returnTypeCoverage = 0;
            }

            if (str_contains($error->getMessage(), 'constant types')) {
                $constantsCoverage = 0;
            }
        }

        return new self(
            $file,
            $errors,
            $errorsIgnored,
            $propertyCoverage,
            $paramCoverage,
            $returnTypeCoverage,
            $constantsCoverage,
            (int) round(($propertyCoverage + $paramCoverage + $returnTypeCoverage + $constantsCoverage) / 4, mode: PHP_ROUND_HALF_DOWN),
        );
    }

    /**
     * Either the project supports constant types or not.
     */
    public static function supportsConstantTypes(): bool
    {
        if (self::$supportsConstantTypes !== null) {
            return self::$supportsConstantTypes;
        }

        $rootPath = TestSuite::getInstance()->rootPath;

        $fallback = version_compare(PHP_VERSION, '8.3.0', '>=');

        $composerJson = json_decode((string) file_get_contents($rootPath.'/composer.json'), true);

        if (! is_array($composerJson) || ! array_key_exists('require', $composerJson)) {
            return $fallback;
        }

        if (! array_key_exists('php', $composerJson['require'])) {
            return $fallback;
        }

        $phpVersion = ltrim($composerJson['require']['php'], '^>=~');

        $isOwnPackage = ($composerJson['name'] ?? '') === 'pestphp/pest-plugin-type-coverage';

        return version_compare($phpVersion, '8.3.0', '>=') || $isOwnPackage;
    }
}
