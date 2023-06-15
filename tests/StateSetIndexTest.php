<?php

namespace Toflar\StateSetIndex\Test;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Alphabet\InMemoryAlphabet;
use Toflar\StateSetIndex\Config;
use Toflar\StateSetIndex\StateSet\InMemoryStateSet;
use Toflar\StateSetIndex\StateSetIndex;

class StateSetIndexTest extends TestCase
{
    public function testResultsMatchResearchPaper(): void
    {
        $stringSet = ['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann'];

        $stateSetIndex = new StateSetIndex(
            new Config(6, 4),
            new InMemoryAlphabet([
                'M' => 1,
                'u' => 2,
                'e' => 3,
                'l' => 4,
                'r' => 1,
                'ü' => 2,
                'n' => 3,
                't' => 4,
                's' => 1,
                'm' => 2,
                'a' => 3,
            ]),
            new InMemoryStateSet()
        );

        $stateSetIndex->index($stringSet);

        $this->assertSame([467, 104, 419, 1677, 1811], $stateSetIndex->findMatchingStates('Mustre', 2));
        $this->assertSame([1811 => ['Mueller'], 1677 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2));
        $this->assertSame(['Muster'], $stateSetIndex->find('Mustre', 2));
    }
}