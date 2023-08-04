<?php

namespace Toflar\StateSetIndex\StateSet;

class InMemoryStateSet implements StateSetInterface
{
    /**
     * Key: State
     * Value: array<parent,mappedChar>
     *
     * @var array<int, array<int,int>>
     */
    private array $states = [];

    /**
     * @var array<int, array<int>>
     */
    private array $children = [];

    /**
     * Key: State
     * Value: Mapped char
     * @var array<int, int>
     */
    private array $mappedChars = [];

    /**
     * Key: State
     * Value: Matching strings
     * @var array<int, array<string>>
     */
    private array $acceptedStrings = [];

    public function add(int $state, int $parentState, int $mappedChar): self
    {
        $this->states[$state] = [$parentState, $mappedChar];
        $this->mappedChars[$state] = $mappedChar;
        $this->children[$parentState][$state] = true;

        return $this;
    }

    public function all(): array
    {
        return $this->states;
    }

    public function getChildrenOfState(int $state): array
    {
        if (! isset($this->children[$state])) {
            return [];
        }

        return array_keys($this->children[$state]);
    }

    public function getCharForState(int $state): int
    {
        if (! isset($this->mappedChars[$state])) {
            throw new \LogicException('No mapped char for state. Check your alphabet!');
        }

        return $this->mappedChars[$state];
    }

    public function acceptString(int $state, string $string): self
    {
        $this->acceptedStrings[$state][] = $string;

        return $this;
    }

    public function getAcceptedStrings(array $matchingStates = []): array
    {
        if ([] === $matchingStates) {
            return $this->acceptedStrings;
        }

        return array_intersect_key($this->acceptedStrings, array_flip($matchingStates));
    }
}
