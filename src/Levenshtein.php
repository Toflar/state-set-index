<?php

namespace Toflar\StateSetIndex;

class Levenshtein
{
    public static function distance(string $string1, string $string2, int $insertionCost = 1, $replacementCost = 1, $deletionCost = 1): int
    {
        $string1 = mb_convert_encoding($string1, 'ASCII', 'utf8');
        $string2 = mb_convert_encoding($string2, 'ASCII', 'utf8');

        if (false === $string1 || false === $string2) {
            throw new \InvalidArgumentException('Both, string1 and string2 have to be valid utf-8 strings.');
        }

        return levenshtein($string1, $string2, $insertionCost, $replacementCost, $deletionCost);
    }

    public static function distanceDamerau(string $string1, string $string2, int $insertionCost = 1, $replacementCost = 1, $deletionCost = 1, $transpositionCost = 1): int
    {
        $string1Length = mb_strlen($string1);
        $string2Length = mb_strlen($string2);
        $matrix = [[]];

        for ($i = 0; $i <= $string1Length; $i += 1) {
            $matrix[$i][0] = $i > 0 ? $matrix[$i - 1][0] + $deletionCost : 0;
        }

        for ($i = 0; $i <= $string2Length; $i += 1) {
            $matrix[0][$i] = $i > 0 ? $matrix[0][$i - 1] + $insertionCost : 0;
        }

        for ($i = 1; $i <= $string1Length; $i += 1) {
            $cOne = mb_substr($string1, $i - 1, 1, 'UTF-8');
            for ($j = 1; $j <= $string2Length; $j += 1) {
                $cTwo = mb_substr($string2, $j - 1, 1, 'UTF-8');

                if ($cOne === $cTwo) {
                    $cost = 0;
                    $trans = 0;
                } else {
                    $cost = $replacementCost;
                    $trans = $transpositionCost;
                }

                // Deletion cost
                $del = $matrix[$i - 1][$j] + $deletionCost;

                // Insertion cost
                $ins = $matrix[$i][$j - 1] + $insertionCost;

                // Substitution cost, 0 if same
                $sub = $matrix[$i - 1][$j - 1] + $cost;

                // Compute optimal
                $matrix[$i][$j] = min($del, $ins, $sub);

                // Transposition cost
                if ($i > 1 && $j > 1) {
                    $ccOne = mb_substr($string1, $i - 2, 1, 'UTF-8');
                    $ccTwo = mb_substr($string2, $j - 2, 1, 'UTF-8');

                    if ($cOne === $ccTwo && $ccOne === $cTwo) {
                        // Transposition cost is computed as minimal of two
                        $matrix[$i][$j] = min($matrix[$i][$j], $matrix[$i - 2][$j - 2] + $trans);
                    }
                }
            }
        }

        return $matrix[$string1Length][$string2Length];
    }
}
