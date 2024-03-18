<?php

declare(strict_types=1);

namespace Toflar\StateSetIndex\Levenshtein;

class Automaton
{
    /**
     * @var array<string>
     */
    private array $chars = [];

    private int $length;

    public function __construct(
        private string $string,
        private int $maxDistance
    ) {
        $this->length = mb_strlen($this->string);
        $this->chars = mb_str_split($this->string);
    }

    /**
     * @param array<int, array<int>> $indicesValues
     */
    public function canMatch(array $indicesValues): bool
    {
        [$indices, $values] = $indicesValues;
        return !empty($indices);
    }

    public function getMaxDistance(): int
    {
        return $this->maxDistance;
    }

    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @param array<int, array<int>> $indicesValues
     */
    public function isMatch(array $indicesValues): bool
    {
        [$indices, $values] = $indicesValues;
        return !empty($indices) && end($indices) === $this->length;
    }

    /**
     * @return array<int, array<int>>
     */
    public function start(): array
    {
        return [range(0, $this->maxDistance), range(0, $this->maxDistance)];
    }

    /**
     * @param array<int, array<int>> $indicesValues
     * @return array<int, array<int>>
     */
    public function step(array $indicesValues, string $c): array
    {
        [$indices, $values] = $indicesValues;
        $new_indices = [];
        $new_values = [];

        if (!empty($indices) && $indices[0] === 0 && $values[0] < $this->maxDistance) {
            $new_indices[] = 0;
            $new_values[] = $values[0] + 1;
        }

        foreach ($indices as $j => $i) {
            if ($i === $this->length) {
                break;
            }
            $cost = ($this->chars[$i] === $c) ? 0 : 1;
            $val = $values[$j] + $cost;

            if (!empty($new_indices) && end($new_indices) === $i) {
                $val = min($val, end($new_values) + 1);
            }

            if ($j + 1 < \count($indices) && $indices[$j + 1] === $i + 1) {
                $val = min($val, $values[$j + 1] + 1);
            }

            if ($val <= $this->maxDistance) {
                $new_indices[] = $i + 1;
                $new_values[] = $val;
            }
        }

        return [$new_indices, $new_values];
    }
}
