<?php

namespace Toflar\StateSetIndex\Test;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\DamerauLevenshtein;
use Toflar\StateSetIndex\Levenshtein;

class DamerauLevenshteinTest extends TestCase
{
    /**
     * @dataProvider distanceProvider
     */
    public function testDistance(int $expected, string $a, string $b, int $maxDistance = PHP_INT_MAX): void
    {
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, $maxDistance));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, $maxDistance));
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, $expected));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, $expected));
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, min($maxDistance, $expected + 1)));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, min($maxDistance, $expected + 1)));

        if ($expected > 0) {
            $this->assertSame($expected - 1, DamerauLevenshtein::distance($a, $b, $expected - 1));
            $this->assertSame($expected - 1, DamerauLevenshtein::distance($b, $a, $expected - 1));
        }

        $this->assertSame($expected * 2, DamerauLevenshtein::distance($a, $b, $maxDistance === PHP_INT_MAX ? PHP_INT_MAX : $maxDistance * 2, 2, 2, 2, 2));
        $this->assertSame($expected * 2, DamerauLevenshtein::distance($b, $a, $maxDistance === PHP_INT_MAX ? PHP_INT_MAX : $maxDistance * 2, 2, 2, 2, 2));
        $this->assertSame($expected * 4, DamerauLevenshtein::distance($a, $b, $maxDistance === PHP_INT_MAX ? PHP_INT_MAX : $maxDistance * 4, 4, 4, 4, 4));
        $this->assertSame($expected * 4, DamerauLevenshtein::distance($b, $a, $maxDistance === PHP_INT_MAX ? PHP_INT_MAX : $maxDistance * 4, 4, 4, 4, 4));
    }

    public static function distanceProvider(): \Generator {
        yield [0, 'abc', 'abc'];

        yield [1, 'abcd', 'abcx'];
        yield [2, 'abcd', 'abxx'];
        yield [3, 'abcd', 'axxx'];
        yield [4, 'abcd', 'xxxx'];

        yield [1, 'abcd', 'abc'];
        yield [2, 'abcd', 'ab'];
        yield [3, 'abcd', 'a'];
        yield [4, 'abcd', ''];

        yield [1, 'abcd', 'abcdx'];
        yield [2, 'abcd', 'xxabcd'];
        yield [3, 'abcd', 'xxxabcd'];
        yield [4, 'abcd', 'xxxxabcd'];
        yield [5, 'abcd', 'xxxxxabcd'];
        yield [6, 'abcd', 'xxxxxxabcd'];

        yield [6, 'abcd', 'xxxxxabcdx'];
        yield [6, 'abcd', 'xxxxabcdxx'];
        yield [6, 'abcd', 'xxxabcdxxx'];
        yield [6, 'abcd', 'xxabcdxxxx'];
        yield [6, 'abcd', 'xabcdxxxxx'];

        yield [1, 'abcdx', 'abcd'];
        yield [2, 'xxabcd', 'abcd'];
        yield [3, 'xxxabcd', 'abcd'];
        yield [4, 'xxxxabcd', 'abcd'];
        yield [5, 'xxxxxabcd', 'abcd'];
        yield [6, 'xxxxxxabcd', 'abcd'];

        yield [6, 'xxxxxabcdx', 'abcd'];
        yield [6, 'xxxxabcdxx', 'abcd'];
        yield [6, 'xxxabcdxxx', 'abcd'];
        yield [6, 'xxabcdxxxx', 'abcd'];
        yield [6, 'xabcdxxxxx', 'abcd'];

        yield [1, 'abcdefg', 'bacdefg'];
        yield [1, 'abcdefg', 'acbdefg'];
        yield [1, 'abcdefg', 'abdcefg'];
        yield [1, 'abcdefg', 'abcedfg'];
        yield [1, 'abcdefg', 'abcdfeg'];
        yield [1, 'abcdefg', 'abcdegf'];

        yield [1, 'ab', 'ba'];
        yield [2, 'ab', 'xba'];
        yield [2, 'ab', 'bax'];

        yield [2, 'abab', 'baba'];
        yield [2, 'abba', 'baab'];
        yield [3, 'abba', 'xbaab'];
        yield [3, 'abba', 'baabx'];
        yield [3, 'abab', 'baxba'];
        yield [3, 'abba', 'baxab'];
        yield [4, 'abba', 'bxaab'];
        yield [4, 'abba', 'baaxb'];

        yield [1, 'abc', 'abcd', 1];
        yield [2, 'abc', 'abcde', 2];
        yield [3, 'abc', 'abcdef', 3];
        yield [4, 'abc', 'abcdefg', 4];

        yield [3, 'aaaaaaaaaa', 'bbbbbbbbbb', 3];
        yield [2, 'aaaaaaaaaa', 'bbbbbbbbbb', 2];
        yield [1, 'aaaaaaaaaa', 'bbbbbbbbbb', 1];
        yield [0, 'aaaaaaaaaa', 'bbbbbbbbbb', 0];

        yield [1, 'xxxxxxxxxx', 'xxxxxxxxx_', 2];
        yield [2, 'xxxxxxxxxx', 'xxxxxxxx__', 3];
        yield [3, 'xxxxxxxxxx', 'xxxxxxx___', 4];

        yield [1, str_repeat('x', 1024), str_repeat('x', 1023).'_', 2];
        yield [2, str_repeat('x', 1024), str_repeat('x', 1022).'__', 3];
        yield [3, str_repeat('x', 1024), str_repeat('x', 1021).'___', 4];

        yield [1, str_repeat('x', 1024), '_'.str_repeat('x', 1023), 2];
        yield [2, str_repeat('x', 1024), '_'.str_repeat('x', 1022).'_', 3];
        yield [3, str_repeat('x', 1024), '_'.str_repeat('x', 1021).'__', 4];
        yield [4, str_repeat('x', 1024), '_'.str_repeat('x', 1020).'___', 5];

        yield [1, '', 'a'];
        yield [1, 'a', ''];

        yield [1, 'héllo', 'hello'];
        yield [2, 'garçonnière', 'garconniere'];
        yield [1, 'garçonnière', 'garçonniere'];
        yield [2, 'Ñörbärm', 'Üörbarm'];
        yield [2, 'garçonnière', 'garconniere'];
        yield [1, 'garçonnière', 'garçonniere'];
        yield [1, 'пожар', 'пажар'];
        yield [1, 'пожар', 'пожаr'];
        yield [2, 'слово', 'слива'];
        yield [4, 'стул', 'вода'];

        yield [1, 'aaäaa', 'aaöaa'];
        yield [1, "prefix\xF0\x9F\x92\xA9", "prefix\xF0\x9F\x92\xAF"];
        yield [1, "prefix\xF0\x9F\x92\xA9", "prefix\xF0\x9F\x93\xA9"];
        yield [1, "\xF0\x9F\x92\xA9suffix", "\xF0\x9F\x92\xAFsuffix"];
        yield [1, "\xF0\x9F\x92\xA9suffix", "\xF0\x9F\x93\xA9suffix"];
        yield [1, "prefix\xF0\x9F\x92\xA9suffix", "prefix\xF0\x9F\x92\xAFsuffix"];
        yield [1, "prefix\xF0\x9F\x92\xA9suffix", "prefix\xF0\x9F\x93\xA9suffix"];

    }

    /**
     * @dataProvider costsProvider
     */
    public function testDifferentCosts(int $expected, string $a, string $b, int $insertionCost = 1, int $replacementCost = 1, int $deletionCost = 1, int $transpositionCost = 1): void
    {
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, PHP_INT_MAX, $insertionCost, $replacementCost, $deletionCost, $transpositionCost));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, PHP_INT_MAX, $deletionCost, $replacementCost, $insertionCost, $transpositionCost));
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, $expected, $insertionCost, $replacementCost, $deletionCost, $transpositionCost));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, $expected, $deletionCost, $replacementCost, $insertionCost, $transpositionCost));
        $this->assertSame($expected, DamerauLevenshtein::distance($a, $b, $expected + 1, $insertionCost, $replacementCost, $deletionCost, $transpositionCost));
        $this->assertSame($expected, DamerauLevenshtein::distance($b, $a, $expected + 1, $deletionCost, $replacementCost, $insertionCost, $transpositionCost));

        if ($expected > 0) {
            $this->assertSame($expected - 1, DamerauLevenshtein::distance($a, $b, $expected - 1, $insertionCost, $replacementCost, $deletionCost, $transpositionCost));
            $this->assertSame($expected - 1, DamerauLevenshtein::distance($b, $a, $expected - 1, $deletionCost, $replacementCost, $insertionCost, $transpositionCost));
        }
    }

    public static function costsProvider(): \Generator
    {
        yield [7, 'abc', 'bcd', 3, 8, 4];
        yield [3, 'abc', 'bcd', 2, 1, 3];
        yield [4, 'abcd', 'acbd', 1, 2, 3, 4];
        yield [4, 'abcd', 'acbd', 2, 2, 3, 5];
        yield [4, 'abcd', 'acbd', 1, 3, 3, 5];
        yield [4, 'abcd', 'acbd', 2, 3, 3, 4];
        yield [5, 'abcd', 'acbd', 2, 3, 3, 5];
        yield [5, 'abcd', 'acbd', 2, 3, 3, 6];
        yield [6, 'abcd', 'acbd', 2, 3, 4, 6];
        yield [1, 'abcd', 'abcde', 1, 2, 2, 2];
        yield [1, 'abcd', 'abcde', 1, 99, 99, 99];
        yield [1, 'abcd', 'aXcd', 2, 1, 2, 2];
        yield [1, 'abcd', 'aXcd', 99, 1, 99, 99];
        yield [1, 'abcd', 'abc', 2, 2, 1, 2];
        yield [1, 'abcd', 'abc', 99, 99, 1, 99];
        yield [1, 'abcd', 'acbd', 2, 2, 2, 1];
        yield [1, 'abcd', 'acbd', 99, 99, 99, 1];
        yield [2, 'abcd', 'abcde', 2, 3, 3, 3];
        yield [2, 'abcd', 'abcde', 2, 99, 99, 99];
        yield [2, 'abcd', 'aXcd', 3, 2, 3, 3];
        yield [2, 'abcd', 'aXcd', 99, 2, 99, 99];
        yield [2, 'abcd', 'abc', 3, 3, 2, 3];
        yield [2, 'abcd', 'abc', 99, 99, 2, 99];
        yield [2, 'abcd', 'acbd', 3, 3, 3, 2];
        yield [2, 'abcd', 'acbd', 99, 99, 99, 2];
        yield [13, 'aaaa', 'bbbbb', 1, 99, 2, 99];
        yield [14, 'aaaaa', 'bbbb', 1, 99, 2, 99];
        yield [14, 'aaaa', 'bbbbb', 2, 99, 1, 99];
        yield [13, 'aaaaa', 'bbbb', 2, 99, 1, 99];
    }
}
