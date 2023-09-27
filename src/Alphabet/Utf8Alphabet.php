<?php

namespace Toflar\StateSetIndex\Alphabet;

class Utf8Alphabet implements AlphabetInterface
{
    /**
     * @var array<int, array<string, int>>
     */
    private array $cache = [];

    public function map(string $char, int $alphabetSize): int
    {
        if (isset($this->cache[$alphabetSize][$char])) {
            return $this->cache[$alphabetSize][$char];
        }

        return $this->cache[$alphabetSize][$char] = mb_ord($char, 'UTF-8') % $alphabetSize;
    }
}
