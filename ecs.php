<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
    ]);

    $ecsConfig->sets([
        SetList::SPACES,
        SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::COMMENTS,
        SetList::PSR_12,
        SetList::CLEAN_CODE,
    ]);

    // Always move private elements to the bottom
    $ecsConfig->ruleWithConfiguration(OrderedClassElementsFixer::class, ['sort_algorithm' => 'alpha']);
    $ecsConfig->rule(NativeFunctionInvocationFixer::class);
    $ecsConfig->skip([NotOperatorWithSuccessorSpaceFixer::class]);
};
