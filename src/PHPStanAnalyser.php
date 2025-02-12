<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use Composer\InstalledVersions;
use PhpParser\Node;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\LocalIgnoresProcessor;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Collectors\Collector;
use PHPStan\Collectors\Registry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Reflection\ClassReflectionExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\DynamicThrowTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\DependencyInjection\Type\ParameterOutTypeExtensionProvider;
use PHPStan\File\FileHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\StubPhpDocProvider;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SignatureMap\SignatureMapProvider;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;

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
        $ruleRegistry = new DirectRegistry($rules);
        $collectorRegistry = new Registry($collectors);

        $reflectionProvider = $container->getByType(ReflectionProvider::class);
        $typeSpecifier = $container->getService('typeSpecifier');

        $scopeFactory = TestCaseForTypeCoverage::createScopeFactory($reflectionProvider, $typeSpecifier); // @phpstan-ignore-line

        $version = InstalledVersions::getPrettyVersion('phpstan/phpstan') ?? InstalledVersions::getPrettyVersion('phpstan/phpstan-src');
        if ($version !== null && version_compare($version, '2.1.3', '>=')) {
            $nodeScopeResolver = new NodeScopeResolver( // @phpstan-ignore-line
                $reflectionProvider,
                $container->getByType(InitializerExprTypeResolver::class),
                $container->getService('betterReflectionReflector'), // @phpstan-ignore-line
                $container->getByType(ClassReflectionExtensionRegistryProvider::class),
                $container->getByType(ParameterOutTypeExtensionProvider::class),
                $container->getService('defaultAnalysisParser'), // @phpstan-ignore-line
                $container->getByType(FileTypeMapper::class),
                $container->getByType(StubPhpDocProvider::class),
                $container->getByType(PhpVersion::class),
                $container->getByType(SignatureMapProvider::class),
                $container->getByType(AttributeReflectionFactory::class),
                $container->getByType(PhpDocInheritanceResolver::class),
                $container->getByType(FileHelper::class),
                $typeSpecifier, // @phpstan-ignore-line
                $container->getByType(DynamicThrowTypeExtensionProvider::class),
                $container->getByType(ReadWritePropertiesExtensionProvider::class),
                $container->getByType(ParameterClosureTypeExtensionProvider::class),
                $scopeFactory,
                false,
                true,
                true, // @phpstan-ignore-line
                [],
                [],
                [], // @phpstan-ignore-line
                true,
                true,
            );
        } elseif ($version !== null && version_compare($version, '2.0.0', '>=')) {
            $nodeScopeResolver = new NodeScopeResolver( // @phpstan-ignore-line
                $reflectionProvider,
                $container->getByType(InitializerExprTypeResolver::class),
                $container->getService('betterReflectionReflector'), // @phpstan-ignore-line
                $container->getByType(ClassReflectionExtensionRegistryProvider::class),
                $container->getByType(ParameterOutTypeExtensionProvider::class),
                $container->getService('defaultAnalysisParser'), // @phpstan-ignore-line
                $container->getByType(FileTypeMapper::class),
                $container->getByType(StubPhpDocProvider::class),
                $container->getByType(PhpVersion::class),
                $container->getByType(SignatureMapProvider::class),
                $container->getByType(PhpDocInheritanceResolver::class),
                $container->getByType(FileHelper::class),
                $typeSpecifier, // @phpstan-ignore-line
                $container->getByType(DynamicThrowTypeExtensionProvider::class),
                $container->getByType(ReadWritePropertiesExtensionProvider::class),
                $container->getByType(ParameterClosureTypeExtensionProvider::class),
                $scopeFactory,
                false,
                true,
                true, // @phpstan-ignore-line
                [],
                [],
                [], // @phpstan-ignore-line
                true,
                true,
            );
        } else {
            $nodeScopeResolver = new NodeScopeResolver(
                $reflectionProvider,
                $container->getByType(InitializerExprTypeResolver::class),
                $container->getService('betterReflectionReflector'), // @phpstan-ignore-line
                $container->getByType(ClassReflectionExtensionRegistryProvider::class),
                $container->getByType(ParameterOutTypeExtensionProvider::class),
                $container->getService('defaultAnalysisParser'), // @phpstan-ignore-line
                $container->getByType(FileTypeMapper::class),
                $container->getByType(StubPhpDocProvider::class),
                $container->getByType(PhpVersion::class),
                $container->getByType(SignatureMapProvider::class),
                $container->getByType(PhpDocInheritanceResolver::class),
                $container->getByType(FileHelper::class),
                $typeSpecifier, // @phpstan-ignore-line
                $container->getByType(DynamicThrowTypeExtensionProvider::class),
                $container->getByType(ReadWritePropertiesExtensionProvider::class),
                $container->getByType(ParameterClosureTypeExtensionProvider::class),
                $scopeFactory,
                false,
                true,
                [],
                [],
                [],
                true,
                true,
                false,
                true,
                false,
                false,
            );
        }

        $fileAnalyser = new FileAnalyser(
            $scopeFactory,
            $nodeScopeResolver,
            $container->getService('defaultAnalysisParser'), // @phpstan-ignore-line
            $container->getByType(DependencyResolver::class),
            new RuleErrorTransformer,
            $container->getByType(LocalIgnoresProcessor::class),
        );

        return new Analyser($fileAnalyser, $ruleRegistry, $collectorRegistry, $nodeScopeResolver, 9_999_999_999_999);
    }
}
