<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Pest\Exceptions\ShouldNotHappen;
use PHPStan\Analyser\Error as PHPStanError;

/**
 * @internal
 */
final class Error
{
    public const TYPE_PROPERTY = 'PROPERTY';

    public const TYPE_PARAM = 'PARAM';

    public const TYPE_RETURN_TYPE = 'RETURN_TYPE';

    /**
     * Creates a new error instance.
     */
    private function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $type,
    ) {
        //
    }

    /**
     * Creates a new error instance.
     */
    public static function fromPHPStanError(PHPStanError $error): self
    {
        return new self(
            $error->getFile(),
            (int) $error->getLine(),
            match (true) {
                str_contains($error->getMessage(), 'property types') => self::TYPE_PROPERTY,
                str_contains($error->getMessage(), 'param types') => self::TYPE_PARAM,
                str_contains($error->getMessage(), 'return types') => self::TYPE_RETURN_TYPE,
                default => throw ShouldNotHappen::fromMessage('Unknown error type: '.$error->getMessage()),
            },
        );
    }

    /**
     * Returns the short type of the error.
     */
    public function getShortType(): string
    {
        return match ($this->type) {
            self::TYPE_PROPERTY => 'pr',
            self::TYPE_PARAM => 'pa',
            self::TYPE_RETURN_TYPE => 'rt',
            default => throw ShouldNotHappen::fromMessage('Unknown error type: '.$this->type),
        };
    }
}
