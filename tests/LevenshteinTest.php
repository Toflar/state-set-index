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
        $this->assertSame(2, Levenshtein::distance('Ñörbärm', 'Üörbarm'));
        $this->assertSame(2, Levenshtein::distance('garçonnière', 'garconniere'));
        $this->assertSame(1, Levenshtein::distance('garçonnière', 'garçonniere'));
        $this->assertSame(1, Levenshtein::distance('пожар', 'пажар'));
        $this->assertSame(1, Levenshtein::distance('пожар', 'пожаr'));
        $this->assertSame(2, Levenshtein::distance('слово', 'слива'));
        $this->assertNotSame(0, Levenshtein::distance('стул', 'вода'));
    }
}