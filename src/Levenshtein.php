<?php

namespace Toflar\StateSetIndex;

class Levenshtein
{
    public static function distance(string $string1, string $string2, int $insertionCost = 1, $replacementCost = 1, $deletionCost = 1)
    {
        $string1 = mb_convert_encoding($string1, 'ASCII', 'utf8');
        $string2 = mb_convert_encoding($string2, 'ASCII', 'utf8');

        if (false === $string1 || false === $string2) {
            throw new \InvalidArgumentException('Both, string1 and string2 have to be valid utf-8 strings.');
        }

        return levenshtein($string1, $string2, $insertionCost, $replacementCost, $deletionCost);
    }
}
