<?php

namespace Toflar\StateSetIndex;

class DamerauLevenshtein
{
    /**
     * Damerau-Levenshtein distance algorithm optimized for a specified maximum.
     *
     * We only use a diagonal corridor of the full matrix, the top right and
     * bottom left area is ignored as they would always be guaranteed to reach
     * the maximum.
     *
     * For a maximum distance of 2, the matrix looks like this and the algorithm
     * would return early with a distance of 2 after calculating row d:
     *
     * ```
     *       a, c, e, d, f, g, h
     *  : 0, 1,  ,  ,  ,  ,  ,
     * a: 1, 0, 1,  ,  ,  ,  ,
     * b:  , 1, 1, 2,  ,  ,  ,
     * c:  ,  , 1, 2, 3,  ,  ,
     * d:  ,  ,  , 2, 2, 3,  ,
     * e:  ,  ,  ,  , 2, 3, 3,
     * f:  ,  ,  ,  ,  , 2, 3, 3
     * g:  ,  ,  ,  ,  ,  , 2, 3
     * ```
     */
    public static function distance(string $string1, string $string2, int $maxDistance = PHP_INT_MAX, int $insertionCost = 1, int $replacementCost = 1, int $deletionCost = 1, int $transpositionCost = 1): int
    {
        if ($string1 === $string2) {
            return 0;
        }

        // Strip common prefix
        $xorLeft = $string1 ^ $string2;
        if ($commonPrefixLength = strspn($xorLeft, "\0")) {
            $string1 = mb_strcut($string1, $commonPrefixLength);
            $string2 = mb_strcut($string2, $commonPrefixLength);
        }

        // Strip common suffix
        $xorRight = substr($string1, -\strlen($string2)) ^ substr($string2, -\strlen($string1));
        if (\strlen($string1) === \strlen($string2) && $commonSuffixLength = \strlen($xorRight) - \strlen(rtrim($xorRight, "\0"))) {
            $suffix = mb_strcut($string1, -$commonSuffixLength);
            if (\strlen($suffix) > $commonSuffixLength) {
                $suffix = mb_substr($suffix, 1);
            }
            $string1 = substr($string1, 0, -\strlen($suffix));
            $string2 = substr($string2, 0, -\strlen($suffix));
        }

        $chars1 = mb_str_split($string1);
        $chars2 = mb_str_split($string2);

        $string1Length = \count($chars1);
        $string2Length = \count($chars2);
        $maxLength = max($string1Length, $string2Length);
        $maxDistance = min($maxDistance, $maxLength);

        $maxDeletions = floor(($maxDistance - ($string1Length - $string2Length)) / 2);
        $maxInsertions = floor(($maxDistance + ($string1Length - $string2Length)) / 2);

        $matrixSize = 1 + $maxDeletions + $maxInsertions;

        // Length difference is too big
        if ($matrixSize <= 1 || $maxDistance <= abs($string1Length - $string2Length)) {
            return $maxDistance;
        }

        // We only store the latest two rows and flip the access between them.
        $matrix = [
            array_fill(0, $matrixSize, $maxDistance),
            array_fill(0, $matrixSize, $maxDistance),
        ];

        for ($i = $maxInsertions; $i < $matrixSize; ++$i) {
            $matrix[0][$i] = $i - $maxInsertions;
        }

        for ($i = 0; $i < $string1Length; ++$i) {
            $currentRow = ($i + 1) % 2;
            $lastRow = $i % 2;
            for ($j = 0; $j < $matrixSize; ++$j) {
                $col = $j - $maxInsertions + $i;
                if ($col < 0) {
                    $matrix[$currentRow][$j] = $i - $col;
                    continue;
                }
                if ($col >= $string2Length) {
                    continue;
                }
                if ($i && ($chars1[$i] ?? '') === ($chars2[$col - 1] ?? '') && ($chars1[$i - 1] ?? '') === ($chars2[$col] ?? '')) {
                    // In this case $matrix[$currentRow][$j] refers to the value
                    // two rows above and two columns to the left in the matrix.
                    $transpositioned = $matrix[$currentRow][$j] + $transpositionCost;
                } else {
                    $transpositioned = $maxDistance;
                }
                $matrix[$currentRow][$j] = min(
                    $transpositioned,
                    ($matrix[$lastRow][$j + 1] ?? $maxDistance) + $deletionCost,
                    ($matrix[$currentRow][$j - 1] ?? $maxDistance) + $insertionCost,
                    ($matrix[$lastRow][$j] ?? $maxDistance) + ((($chars1[$i] ?? '') === ($chars2[$col] ?? '')) ? 0 : $replacementCost),
                );
            }

            if (min($matrix[$currentRow]) >= $maxDistance) {
                return $maxDistance;
            }
        }

        return min($maxDistance, $matrix[$currentRow ?? 0][$maxInsertions - ($string1Length - $string2Length)]);
    }
}
