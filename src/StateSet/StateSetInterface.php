<?php

namespace Toflar\StateSetIndex\StateSet;

interface StateSetInterface
{
    public function add(int $newState, int $parentState, int $mappedChar): self;

    public function getChildrenOfState(int $state): array;

    /**
     * Returns all the children of a given start state that are smaller or equal to $editDistance.
     * The $startState has to be included in the resulting CostAnnotatedStateSet if the edit distance is fulfilled as
     * well (a state is always able to reach itself).
     */
    public function getReachableStates(int $startState, int $editDistance, int $currentDistance = 0): CostAnnotatedStateSet;

    public function getCharForState(int $state): int;

    /**
     * Accept a string with a given state.
     */
    public function acceptString(int $state, string $string): self;

    /**
     * Returns the matching strings per state. Key is the state and the value is an array of matching strings
     * for that state. If no argument is passed, the entire accepted strings dataset is returned.
     *
     * @return array<int,array<string>>
     */
    public function getAcceptedStrings(array $matchingStates = []): array;
}
