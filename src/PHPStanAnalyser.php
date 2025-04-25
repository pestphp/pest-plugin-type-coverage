<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry;
use PHPStan\DependencyInjection\Container;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Rule;

/**
 * @internal
 */
final class PHPStanAnalyser
{
    /**
     * Creates an analyser with the rules and collectors needed for type coverage.
     *
     * @param  array<int, Rule>  $rules
     * @param  array<int, Collector<Node, mixed>>  $collectors
     */
    public static function make(Container $container, array $rules, array $collectors): Analyser
    {
        return new Analyser(
            fileAnalyser: $container->getByType(FileAnalyser::class),
            ruleRegistry: new DirectRegistry($rules),
            collectorRegistry: new Registry($collectors),
            nodeScopeResolver: $container->getByType(NodeScopeResolver::class),
            internalErrorsCountLimit: 9_999_999_999_999
        );
    }
}
