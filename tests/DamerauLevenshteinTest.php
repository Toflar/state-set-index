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

        yield [1, '', 'a'];
        yield [1, 'a', ''];

    }
}
