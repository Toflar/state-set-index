<?php

namespace Toflar\StateSetIndex\StateSet;

class InMemoryStateSet implements StateSetInterface
{
    /**
     * Key: State
     * Value: Children
     * @var array<int, array>
     */
    private array $states = [];

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

    public function add(int $newState, int $parentState, int $mappedChar): self
    {
        $this->mappedChars[$newState] = $mappedChar;

        if (! isset($this->states[$parentState])) {
            $this->states[$parentState] = [];
        }

        $this->states[$parentState][] = $newState;

        return $this;
    }

    public function getChildrenOfState(int $state): array
    {
        return $this->states[$state] ?? [];
    }

    public function getReachableStates(int $startState, int $editDistance, int $currentDistance = 0): CostAnnotatedStateSet
    {
        $reachable = new CostAnnotatedStateSet();

        if ($currentDistance > $editDistance) {
            return $reachable;
        }

        // A state is always able to reach itself
        $reachable->add($startState, $currentDistance);

        foreach ($this->getChildrenOfState($startState) as $child) {
            $reachable = $reachable->mergeWith($this->getReachableStates($child, $editDistance, $currentDistance + 1));
        }

        return $reachable;
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
