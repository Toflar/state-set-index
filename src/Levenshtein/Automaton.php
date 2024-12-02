<?php

declare(strict_types=1);

namespace Toflar\StateSetIndex\Levenshtein;

class Automaton
{
    private array $chars;

    private int $length;

    public function __construct(
        private string $string,
        private int $maxDistance,
        private int $insertionCost = 1,
        private int $deletionCost = 1,
        private int $replacementCost = 1,
        private int $transpositionCost = 1
    ) {
        $this->length = mb_strlen($this->string);
        $this->chars = mb_str_split($this->string);
    }

    public function canMatch(array $state): bool
    {
        foreach ($state as $distance) {
            if ($distance <= $this->maxDistance) {
                return true;
            }
        }
        return false;
    }

    public function isMatch(array $state): bool
    {
        return isset($state[$this->length]) && $state[$this->length] <= $this->maxDistance;
    }

    public function start(): array
    {
        return [
            0 => 0,
        ];
    }

    public function step(array $state, string $inputChar): array
    {
        $newState = [];
        foreach ($state as $position => $distance) {
            if ($distance > $this->maxDistance) {
                continue;
            }

            // Insertion: Stay in the same position in the target
            $newState[$position] = min($newState[$position] ?? PHP_INT_MAX, $distance + $this->insertionCost);

            // Deletion: Move forward in the target
            if ($position < $this->length) {
                $newState[$position + 1] = min($newState[$position + 1] ?? PHP_INT_MAX, $distance + $this->deletionCost);
            }

            // Replacement or Match: Move forward in the target and input
            if ($position < $this->length) {
                $replacementCost = ($this->chars[$position] === $inputChar) ? 0 : $this->replacementCost;
                $newState[$position + 1] = min($newState[$position + 1] ?? PHP_INT_MAX, $distance + $replacementCost);
            }

            // Transposition: Swap adjacent characters
            if (
                $position < $this->length - 1 &&
                isset($this->chars[$position + 1]) &&
                $this->chars[$position + 1] === $inputChar
            ) {
                $newState[$position + 2] = min($newState[$position + 2] ?? PHP_INT_MAX, $distance + $this->transpositionCost);
            }
        }

        return $newState;
    }
}
