<?php

namespace Toflar\StateSetIndex\Test;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Alphabet\InMemoryAlphabet;
use Toflar\StateSetIndex\Alphabet\Utf8Alphabet;
use Toflar\StateSetIndex\Config;
use Toflar\StateSetIndex\DataStore\InMemoryDataStore;
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

        $this->assertSame([104, 419, 467, 1677, 1811], $stateSetIndex->findMatchingStates('Mustre', 2, 2));
        $this->assertSame([1811 => ['Mueller'], 1677 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2, 2));
        $this->assertSame(['Muster'], $stateSetIndex->find('Mustre', 2, 2));
    }

    public function testWithUtf8Alphabet(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muster', 'Mustermann']);

        $this->assertSame([177, 710, 2710, 2843], $stateSetIndex->findMatchingStates('Mustre', 2, 2));
        $this->assertSame([2710 => ['Mueller'], 2843 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2, 2));
        $this->assertSame(['Muster'], $stateSetIndex->find('Mustre', 2));
    }

    /**
     * This use case occurred while testing 2.0.0, which is why this is added as additional test case.
     */
    public function testAssassinCanBeFound(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(14, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['assassin']);

        $this->assertSame([844, 3380, 13522, 54091], $stateSetIndex->findMatchingStates('assasin', 2, 2));
        $this->assertSame([54091 => ['assassin']], $stateSetIndex->findAcceptedStrings('assasin', 2, 2));
        $this->assertSame(['assassin'], $stateSetIndex->find('assasin', 2, 2));
    }

    public function testTranspositionsCanBeFound(): void
    {
        $dataStore = new InMemoryDataStore();
        $stateSetIndex = new StateSetIndex(new Config(14, 6), new Utf8Alphabet(), new InMemoryStateSet(), $dataStore);
        $stateSetIndex->index(['abcdefg']);

        $this->assertSame([123128 => ['abcdefg']], $stateSetIndex->findAcceptedStrings('abdcefg', 1, 1));
        $this->assertSame([123128 => ['abcdefg']], $stateSetIndex->findAcceptedStrings('bacdegf', 2, 1));
        $this->assertSame([], $stateSetIndex->findAcceptedStrings('abdcefg', 0, 1));
        $this->assertSame([], $stateSetIndex->findAcceptedStrings('bacdegf', 1, 1));

        $this->assertSame(['abcdefg'], $stateSetIndex->find('abdcefg', 1));
        $this->assertSame(['abcdefg'], $stateSetIndex->find('bacdegf', 2));
        $this->assertSame([], $stateSetIndex->find('abdcefg', 0));
        $this->assertSame([], $stateSetIndex->find('bacdegf', 1));
    }

    public function testRemoveFromIndex(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller']);

        $onlyMuellerStates = $stateSetIndex->getStateSet()->all();

        $stateSetIndex->removeFromIndex(['Mueller']);

        $this->assertSame([], $stateSetIndex->getStateSet()->all());

        $stateSetIndex->index(['Müller', 'Muentner', 'Muster', 'Mustermann', 'Mueller']);
        $stateSetIndex->removeFromIndex(['Müller', 'Muentner', 'Muster', 'Mustermann']);

        $this->assertEquals($onlyMuellerStates, $stateSetIndex->getStateSet()->all());
        $this->assertSame(['Mueller'], $stateSetIndex->find('Mueler', 1));
    }

    public function testRemoveFromFullIndex(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(5, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller']);

        $onlyMuellerStates = $stateSetIndex->getStateSet()->all();

        $stateSetIndex->removeFromIndex(['Mueller']);

        $this->assertSame([], $stateSetIndex->getStateSet()->all());

        for ($i = 0; $i < $stateSetIndex->getConfig()->getAlphabetSize(); ++$i) {
            $strings[] = \IntlChar::chr(97 + $i);
        }

        for ($length = 1; $length <= $stateSetIndex->getConfig()->getIndexLength(); ++$length) {
            foreach ($strings as $string) {
                for ($i = 0; $i < $stateSetIndex->getConfig()->getAlphabetSize(); ++$i) {
                    $strings[] = $string . \IntlChar::chr(97 + $i);
                }
            }
        }

        // Fill every possible state for the configured length and size
        $stateSetIndex->index($strings);
        $stateSetIndex->index(['Mueller']);

        $states = $stateSetIndex->getStateSet()->all();
        sort($states);

        $this->assertSame(range(1, (((4 * 4 + 4) * 4 + 4) * 4 + 4) * 4 + 4), $states, 'No state should be missing');

        $stateSetIndex->removeFromIndex($strings);

        $this->assertEquals($onlyMuellerStates, $stateSetIndex->getStateSet()->all());
    }
}
