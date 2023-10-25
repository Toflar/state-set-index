<?php

namespace Toflar\StateSetIndex\Test\Levenshtein;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Levenshtein\Automaton;
use Toflar\StateSetIndex\Levenshtein\TrieFilter;

class TrieFilterTest extends TestCase
{

    public static function filterProvider(): \Generator
    {
        yield [
            'helo',
            1,
            ['hello', 'hola', 'helo', 'nonsense', 'elo'],
            [
                'hello',
                2 => 'helo',
                4 => 'elo',
            ],
        ];

        yield [
            'héllo',
            1,
            ['hello', 'hola', 'helo', 'nonsense', 'elo'],
            ['hello'],
        ];

        yield [
            'garçonnière',
            2,
            [
                33 => 'garconniere',
                16 => 'helo',
                48 => 'garçonniere',
                13 => 'nonsense',
                80 => 'elo',
            ],
            [
                33 => 'garconniere',
                48 => 'garçonniere',
            ],
        ];
    }

    /**
     * @param array<int|string, string> $stringsToFilter
     * @param array<int|string, string> $expectedFiltered
     */
    #[DataProvider('filterProvider')]
    public function testFiltering(string $query, int $maxDistance, array $stringsToFilter, array $expectedFiltered): void
    {
        $filter = new TrieFilter(new Automaton($query, $maxDistance));

        $this->assertSame($expectedFiltered, $filter->filterStrings($stringsToFilter));
    }

    public function testMatches(): void
    {
        $filter = new TrieFilter(new Automaton('foobar', 2));
        $this->assertTrue($filter->matches('foobar'));
        $this->assertTrue($filter->matches('foob'));
        $this->assertTrue($filter->matches('boobar'));
        $this->assertTrue($filter->matches('foobix'));
        $this->assertFalse($filter->matches('example'));
    }

    public function testDoesNotMatchIfPrefixIdenticalButLengthDoesNotMatch(): void
    {
        $filter = new TrieFilter(new Automaton('Muster', 2));
        $this->assertFalse($filter->matches('Mustermann'));
    }

    public function testMatchesIfPrefixLengthDoesNotMatchButLevenshteinDoes(): void
    {
        $filter = new TrieFilter(new Automaton('assasin', 2));
        $this->assertTrue($filter->matches('assassin'));
    }
}