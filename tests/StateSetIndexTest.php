<?php

namespace Toflar\StateSetIndex\Test;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Alphabet\InMemoryAlphabet;
use Toflar\StateSetIndex\Alphabet\Utf8Alphabet;
use Toflar\StateSetIndex\Config;
use Toflar\StateSetIndex\DataStore\InMemoryDataStore;
use Toflar\StateSetIndex\DataStore\NullDataStore;
use Toflar\StateSetIndex\StateSet\InMemoryStateSet;
use Toflar\StateSetIndex\StateSetIndex;

class StateSetIndexTest extends TestCase
{
    public function testResultsMatchResearchPaper(): void
    {
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
            new InMemoryStateSet(),
            new InMemoryDataStore()
        );

        $stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann']);

        $this->assertSame([104, 419, 467, 1677, 1811], $stateSetIndex->findMatchingStates('Mustre', 2));
        $this->assertSame([1811 => ['Mueller'], 1677 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2));
        $this->assertSame(['Muster'], $stateSetIndex->find('Mustre', 2));

        // Should consider transposition (Damerau-Levenshtein) as distance of 2
        $this->assertSame([104, 419, 467, 1677, 1811], $stateSetIndex->findMatchingStates('Mustremann', 2));
        $this->assertSame(['Mustermann'], $stateSetIndex->find('Mustremann', 2));
        $this->assertSame([419], $stateSetIndex->findMatchingStates('Mustremann', 1));
        $this->assertSame([], $stateSetIndex->find('Mustremann', 1));
    }

    public function testWithUtf8Alphabet(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann']);

        $this->assertSame([177, 710, 2710, 2843], $stateSetIndex->findMatchingStates('Mustre', 2));
        $this->assertSame([2710 => ['Mueller'], 2843 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2));
        $this->assertSame(['Muster'], $stateSetIndex->find('Mustre', 2));
    }

    public function testDamerauLevenshtein(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann']);

        // Should consider transposition (Damerau-Levenshtein) as distance of 1
        $this->assertSame([677, 710, 2710, 2743, 2843], $stateSetIndex->findMatchingStates('Mustremann', 1, true));
        $this->assertSame([2710 => ['Mueller'], 2743 => ['Muentner'], 2843 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustremann', 1, true));
        $this->assertSame(['Mustermann'], $stateSetIndex->find('Mustremann', 1, true));
    }
}