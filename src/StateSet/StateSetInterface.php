<?php

namespace Toflar\StateSetIndex\StateSet;

interface StateSetInterface
{
    public function add(int $state, int $parentState, int $mappedChar): self;

    public function getChildrenOfState(int $state): array;

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
