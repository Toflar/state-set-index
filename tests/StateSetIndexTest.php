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
    /**
     * There is one divergence from the research paper because the state 1869 is
     * also found as a matching state as a result of potentially matching
     * characters that are cut off by the index length. This is demonstrated by
     * adding the word “Multere” that is mapped to the exact same state as
     * “Müller” is (1869).
     *
     * Finding “Multere” is correct for the search of “Mustre” with an edit
     * distance of 2 because it only requires one substitution and one deletion.
     *
     * It is therefore an error in the original research paper that the state
     * 1869 was not found. The reason is probably because of not handling the
     * special case of matching characters that are cut off by the index length.
     */
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

        $stateSetIndex->index(['Mueller', 'Müller', 'Multere', 'Muentner', 'Muster', 'Mustermann']);

        $this->assertSame([104, 419, 467, 1677, 1811, 1869], $stateSetIndex->findMatchingStates('Mustre', 2, 2));
        $this->assertSame([1811 => ['Mueller'], 1869 => ['Müller', 'Multere'], 1677 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2, 2));
        $this->assertSame(['Multere', 'Muster'], $stateSetIndex->find('Mustre', 2, 2));
    }

    public function testWithUtf8Alphabet(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mueller', 'Müller', 'Muentner', 'Muentre', 'Muster', 'Mustermann']);

        $this->assertSame([177, 710, 2710, 2743, 2843], $stateSetIndex->findMatchingStates('Mustre', 2, 2));
        $this->assertSame([2710 => ['Mueller'], 2743 => ['Muentner', 'Muentre'], 2843 => ['Muster', 'Mustermann']], $stateSetIndex->findAcceptedStrings('Mustre', 2, 2));
        $this->assertSame(['Muentre', 'Muster'], $stateSetIndex->find('Mustre', 2));
    }

    public function testWithLongWordSingleDeletion(): void
    {
        $stateSetIndex = new StateSetIndex(new Config(6, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $stateSetIndex->index(['Mustermann']);

        $this->assertSame([2, 10, 44, 177, 710, 2843], $stateSetIndex->getStateSet()->all());
        $this->assertSame([2843], $stateSetIndex->findMatchingStates('Mutermann', 1, 1));
        $this->assertSame([2843 => ['Mustermann']], $stateSetIndex->findAcceptedStrings('Mutermann', 1, 1));
        $this->assertSame(['Mustermann'], $stateSetIndex->find('Mutermann', 1));
    }

    /**
     * @dataProvider cutOffMatchingProvider
     */
    public function testWithWordsCutOffByIndexLength(string $word, string $search, int $indexLength = 6, int $editDistance = 1): void
    {
        $stateSetIndex = new StateSetIndex(new Config($indexLength, 32), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
        $state = $stateSetIndex->index([$word])[$word];

        $this->assertSame([$word], $stateSetIndex->find($search, $editDistance));
        $this->assertSame([$state => [$word]], $stateSetIndex->findAcceptedStrings($search, $editDistance, 1));

        $this->assertSame([], $stateSetIndex->find($search, $editDistance - 1));
    }

    public static function cutOffMatchingProvider(): \Generator
    {
        yield ['Multere', 'Mustre', 6, 2];
        yield ['Mueller', 'Mueler', 6, 1];
        yield ['Mustermann', 'Mutermann', 6, 1];
        yield ['assassin', 'assasin', 7, 1];
        yield ['abcdefghijkl', 'abcdeghijkl', 6, 1];
        yield ['abcdefghijkl', 'abcdghijkl', 6, 2];
        yield ['abcdefghijkl', 'abcghijkl', 6, 3];
        yield ['abcdefghijkl', 'abghijkl', 6, 4];
        yield ['abcdefghijkl', 'aghijkl', 6, 5];
        yield ['abcdefghijkl', 'abcdefhijkl', 6, 1];
        yield ['abcdefghijkl', 'abcdehijkl', 6, 2];
        yield ['abcdefghijkl', 'abcdeijkl', 6, 3];
        yield ['abcdefghijkl', 'abcdijkl', 6, 4];
        yield ['abcdefghijkl', 'abcdjkl', 6, 5];
        yield ['abcdefghijkl', 'bcdegfhijkl', 6, 2];
        yield ['abcdefghijkl', 'bcdefghijkl', 6, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 5, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 4, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 3, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 2, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 1, 1];
        yield ['abcdefghijkl', 'bcdefghijkl', 6, 1];
        yield ['abcdefghijkl', 'cdefghijkl', 6, 2];
        yield ['abcdefghijkl', 'defghijkl', 6, 3];
        yield ['abcdefghijkl', 'efghijkl', 6, 4];
        yield ['abcdefghijkl', 'fghijkl', 6, 5];
        yield ['abcdefghijk', 'bcdefghijk', 6, 1];
        yield ['abcdefghijk', 'cdefghijk', 6, 2];
        yield ['abcdefghijk', 'defghijk', 6, 3];
        yield ['abcdefghijk', 'efghijk', 6, 4];
        yield ['abcdefghijk', 'fghijk', 6, 5];
        yield ['abcdefghij', 'bcdefghij', 6, 1];
        yield ['abcdefghij', 'cdefghij', 6, 2];
        yield ['abcdefghij', 'defghij', 6, 3];
        yield ['abcdefghij', 'efghij', 6, 4];
        yield ['abcdefghij', 'fghij', 6, 5];
        yield ['abcdefghi', 'bcdefghi', 6, 1];
        yield ['abcdefghi', 'cdefghi', 6, 2];
        yield ['abcdefghi', 'defghi', 6, 3];
        yield ['abcdefghi', 'efghi', 6, 4];
        yield ['abcdefghi', 'fghi', 6, 5];
        yield ['abcdefgh', 'bcdefgh', 6, 1];
        yield ['abcdefgh', 'cdefgh', 6, 2];
        yield ['abcdefgh', 'defgh', 6, 3];
        yield ['abcdefgh', 'efgh', 6, 4];
        yield ['abcdefgh', 'fgh', 6, 5];
        yield ['abcdefg', 'bcdefg', 6, 1];
        yield ['abcdefg', 'cdefg', 6, 2];
        yield ['abcdefg', 'defg', 6, 3];
        yield ['abcdefg', 'efg', 6, 4];
        yield ['abcdefg', 'fg', 6, 5];
        yield ['abcdef', 'bcdef', 6, 1];
        yield ['abcdef', 'cdef', 6, 2];
        yield ['abcdef', 'def', 6, 3];
        yield ['abcdef', 'ef', 6, 4];
        yield ['abcdef', 'f', 6, 5];
    }

    /**
     * @dataProvider cutOffNotMatchingProvider
     */
    public function testWithWordsCutOffByIndexLengthNotMatching(string $word, string $search, int $indexLength = 6, int $editDistance = 1): void
    {
        $stateSetIndex = new StateSetIndex(new Config($indexLength, 32), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());

        $this->assertSame([], $stateSetIndex->find($search, $editDistance));
        $this->assertSame([], $stateSetIndex->findAcceptedStrings($search, $editDistance, 1));
    }

    public static function cutOffNotMatchingProvider(): \Generator
    {
        yield ['Multere', 'Mustre', 6, 1];
        yield ['Mueller', 'Muelar', 6, 1];
        yield ['Mueller', 'M_ueler', 6, 1];
        yield ['abccdc', 'abadac', 6, 2];
        yield ['abcdefghijkl', 'cdefghijkl', 6, 1];
        yield ['abcdefghijkl', 'defghijkl', 6, 2];
        yield ['abcdefghijkl', 'efghijkl', 6, 3];
        yield ['abcdefghijkl', 'fghijkl', 6, 4];
        yield ['abcdefghijkl', 'ghijkl', 6, 5];
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
        $stateSetIndex = new StateSetIndex(new Config(14, 4), new Utf8Alphabet(), new InMemoryStateSet(), new InMemoryDataStore());
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
