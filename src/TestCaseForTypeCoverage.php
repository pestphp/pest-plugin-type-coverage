<?php

declare(strict_types=1);

namespace Pest\TypeCoverage;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\RuleErrorTransformer;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Collectors\Collector;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\DirectRegistry;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Testing\RuleTestCase;
use TomasVotruba\TypeCoverage\Collectors\ParamTypeDeclarationCollector;
use TomasVotruba\TypeCoverage\Collectors\PropertyTypeDeclarationCollector;
use TomasVotruba\TypeCoverage\Collectors\ReturnTypeDeclarationCollector;
use TomasVotruba\TypeCoverage\Rules\ParamTypeCoverageRule;
use TomasVotruba\TypeCoverage\Rules\PropertyTypeCoverageRule;
use TomasVotruba\TypeCoverage\Rules\ReturnTypeCoverageRule;

/**
 * @internal
 */
final class TestCaseForTypeCoverage extends RuleTestCase
{
    private string $ignoreIdentifier = '@pest-type-ignore';

    /**
     * Creates
     */
    public function __construct()
    {
        parent::__construct('testDummy');

        //
    }

    /**
     * An example test.
     */
    public function testDummy(): void
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        $baseDirectory = file_exists(__DIR__.'/../vendor')
            ? __DIR__.'/../'
            : __DIR__.'/../../../../';

        return [
            $baseDirectory.'vendor/tomasvotruba/type-coverage/config/extension.neon',
            __DIR__.'/../resources/phpstan.neon',
        ];
    }

    /**
     * Gets the collectors.
     *
     * @return array<int, Collector<Node, mixed>>
     */
    public function getCollectors(): array
    {
        return [
            self::getContainer()->getByType(ParamTypeDeclarationCollector::class),
            self::getContainer()->getByType(PropertyTypeDeclarationCollector::class),
            self::getContainer()->getByType(ReturnTypeDeclarationCollector::class),
        ];
    }

    /**
     * Gets the rules.
     *
     * @return array<int, Rule>
     */
    public function getRules(): array
    {
        return [
            TestCaseForTypeCoverage::getContainer()->getByType(ParamTypeCoverageRule::class),
            TestCaseForTypeCoverage::getContainer()->getByType(PropertyTypeCoverageRule::class),
            TestCaseForTypeCoverage::getContainer()->getByType(ReturnTypeCoverageRule::class),
        ];
    }

    public function gatherAnalyserErrors(array $files): array
    {
        $files = array_map(fn (string $originalPath, string $directorySeparator = \DIRECTORY_SEPARATOR): string => $this->getFileHelper()->normalizePath($originalPath, $directorySeparator), $files);
        $analyser = PHPStanAnalyser::make(self::getContainer(), $this->getRules(), $this->getCollectors());
        $analyserResult = $analyser->analyse($files, null, null, \true);
        if ($analyserResult->getInternalErrors() !== []) {
            self::fail(implode("\n", $analyserResult->getInternalErrors()));
        }

        $actualErrors = $analyserResult->getUnorderedErrors();
        $ruleErrorTransformer = new RuleErrorTransformer();
        if ($analyserResult->getCollectedData() !== []) {
            $ruleRegistry = new DirectRegistry($this->getRules());
            $nodeType = CollectedDataNode::class;
            $node = new CollectedDataNode($analyserResult->getCollectedData());
            $scopeFactory = $this->createScopeFactory($this->createReflectionProvider(), $this->getTypeSpecifier());
            $scope = $scopeFactory->create(ScopeContext::create('irrelevant'));
            foreach ($ruleRegistry->getRules($nodeType) as $rule) {
                $ruleErrors = $rule->processNode($node, $scope);
                foreach ($ruleErrors as $ruleError) {
                    if (! $this->ignored($ruleError)) {
                        $actualErrors[] = $ruleErrorTransformer->transform($ruleError, $scope, $nodeType, $node->getLine());
                    }
                }
            }
        }

        return $actualErrors;
    }

    /**
     * Check if ignored.
     */
    private function ignored(RuleError $ruleError): bool
    {
        $file = file($ruleError->file);
        $lineContent = $file[$ruleError->line - 1];

        return strpos($lineContent, $this->ignoreIdentifier) !== false;
    }

    /**
     * Gets the tested rule.
     */
    protected function getRule(): Rule
    {
        return new class implements Rule
        {
            public function getNodeType(): string
            {
                return Class_::class;
            }

            public function processNode(Node $node, Scope $scope): array
            {
                return [];
            }
        };
    }
}
