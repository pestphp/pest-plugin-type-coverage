<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use PHPStan\Analyser\Error as PHPStanError;

/**
 * @internal
 */
final class Result
{
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
            || str_contains($error->getMessage(), 'return types');

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

        foreach ($phpstanErrors as $error) {
            if (str_contains($message = $error->getMessage(), 'property types')) {
                $propertyCoverage = (int) explode(' ', explode('only ', $message)[1])[2];
            }
            if (str_contains($error->getMessage(), 'param types')) {
                $paramCoverage = (int) explode(' ', explode('only ', $message)[1])[2];
            }
            if (str_contains($error->getMessage(), 'return types')) {
                $returnTypeCoverage = (int) explode(' ', explode('only ', $message)[1])[2];
            }
        }

        return new self(
            $file,
            $errors,
            $errorsIgnored,
            $propertyCoverage,
            $paramCoverage,
            $returnTypeCoverage,
            (int) round(($propertyCoverage + $paramCoverage + $returnTypeCoverage) / 3, mode: PHP_ROUND_HALF_DOWN),
        );
    }
}
