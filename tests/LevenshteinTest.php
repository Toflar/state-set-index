<?php

namespace Toflar\StateSetIndex\Test;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Levenshtein;

class LevenshteinTest extends TestCase
{
    public function testLevenshtein(): void
    {
        $this->assertSame(1, Levenshtein::distance('hello', 'helo'));
        $this->assertSame(2, Levenshtein::distance('hello', 'heo'));
        $this->assertSame(1, Levenshtein::distance('héllo', 'hello'));
        $this->assertSame(2, Levenshtein::distance('garçonnière', 'garconniere'));
        $this->assertSame(1, Levenshtein::distance('garçonnière', 'garçonniere'));

        // Transposition (o and ç are swapped = distance of 2 in regular Levenshtein)
        $this->assertSame(2, Levenshtein::distance('garçonnière', 'garoçnnière'));
    }

    public function testDamerauLevenshtein(): void
    {
        $this->assertSame(1, Levenshtein::distanceDamerau('hello', 'helo'));
        $this->assertSame(2, Levenshtein::distanceDamerau('hello', 'heo'));
        $this->assertSame(1, Levenshtein::distanceDamerau('héllo', 'hello'));
        $this->assertSame(2, Levenshtein::distanceDamerau('garçonnière', 'garconniere'));
        $this->assertSame(1, Levenshtein::distanceDamerau('garçonnière', 'garçonniere'));

        // Transposition (o and ç are swapped = distance of 1 in Damerau-Levenshtein)
        $this->assertSame(1, Levenshtein::distanceDamerau('garçonnière', 'garoçnnière'));
    }
}