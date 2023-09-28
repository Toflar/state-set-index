<?php

namespace Toflar\StateSetIndex\Test\Alphabet;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Alphabet\Utf8Alphabet;

class Utf8AlphabetTest extends TestCase
{
    public function testAlphabet(): void
    {
        $alphabet = new Utf8Alphabet();
        $this->assertSame(98, $alphabet->map('a', 100)); // a is #97
        $this->assertSame(8, $alphabet->map('a', 10));
        $this->assertSame(8, $alphabet->map('a', 10)); // Testing repetitive calls
        $this->assertSame(5, $alphabet->map('@', 10));
        $this->assertSame(4, $alphabet->map('!', 10));
        $this->assertSame(4, $alphabet->map('é', 10));
        $this->assertSame(10, $alphabet->map('愛', 10));
        $this->assertSame(1, $alphabet->map('(', 10)); // Must be 1, not 0
    }
}