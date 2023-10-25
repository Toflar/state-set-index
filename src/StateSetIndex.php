<?php

namespace Toflar\StateSetIndex;

use Toflar\StateSetIndex\Alphabet\AlphabetInterface;
use Toflar\StateSetIndex\DataStore\DataStoreInterface;
use Toflar\StateSetIndex\Levenshtein\Automaton;
use Toflar\StateSetIndex\Levenshtein\TrieFilter;
use Toflar\StateSetIndex\StateSet\CostAnnotatedStateSet;
use Toflar\StateSetIndex\StateSet\StateSetInterface;

class StateSetIndex
{
    /**
     * @var array<string, int>
     */
    private array $indexCache = [];

    /**
     * @var array<string, int>
     */
    private array $matchingStatesCache = [];

    /**
     * @var array<string, TrieFilter>
     */
    private array $trieFilters = [];

    public function __construct(
        private Config $config,
        private AlphabetInterface $alphabet,
        public StateSetInterface $stateSet,
        private DataStoreInterface $dataStore,
    ) {
    }

    /**
     * Returns the matching strings.
     *
     * @return array<string>
     */
    public function find(string $string, int $editDistance): array
    {
        $cacheKey = $string . "\0" . $editDistance;
        $acceptedStringsPerState = $this->findAcceptedStrings($string, $editDistance);
        $filtered = [];

        if (!isset($this->trieFilters[$cacheKey])) {
            $this->trieFilters[$cacheKey] = new TrieFilter(new Automaton($string, $editDistance));
        }

        foreach ($acceptedStringsPerState as $acceptedStrings) {
            foreach ($acceptedStrings as $acceptedString) {
                if ($this->trieFilters[$cacheKey]->matches($acceptedString)) {
                    $filtered[] = $acceptedString;
                }
            }
        }

        return array_unique($filtered);
    }

    /**
     * Returns the matching strings per state. Key is the state and the value is an array of matching strings
     * for that state.
     *
     * @return array<int,array<string>>
     */
    public function findAcceptedStrings(string $string, int $editDistance): array
    {
        return $this->dataStore->getForStates($this->findMatchingStates($string, $editDistance));
    }

    /**
     * Returns the matching states.
     *
     * @return array<int>
     */
    public function findMatchingStates(string $string, int $editDistance): array
    {
        $cacheKey = $string . ';' . $editDistance;

        // Seen this already, skip
        if (isset($this->matchingStatesCache[$cacheKey])) {
            return $this->matchingStatesCache[$cacheKey];
        }

        // Initial states
        $states = $this->getReachableStates(0, $editDistance);

        $this->loopOverEveryCharacter($string, function (int $mappedChar) use (&$states, $editDistance) {
            $statesStar = new CostAnnotatedStateSet(); // This is S∗ in the paper

            foreach ($states->all() as $state => $cost) {
                $statesStarC = new CostAnnotatedStateSet(); // This is S∗c in the paper

                // Deletion
                if ($cost + 1 <= $editDistance) {
                    $statesStarC->add($state, $cost + 1);
                }

                // Match & Substitution
                for ($i = 1; $i <= $this->config->getAlphabetSize(); $i++) {
                    $newState = (int) ($state * $this->config->getAlphabetSize() + $i);

                    if ($this->stateSet->has($newState)) {
                        if ($i === $mappedChar) {
                            // Match
                            $statesStarC->add($newState, $cost);
                        } elseif ($cost + 1 <= $editDistance) {
                            // Substitution
                            $statesStarC->add($newState, $cost + 1);
                        }
                    }
                }

                // Insertion
                foreach ($statesStarC->all() as $newState => $newCost) {
                    $statesStar = $statesStar->mergeWith($this->getReachableStates(
                        $newState,
                        $editDistance,
                        $newCost
                    ));
                }
            }

            $states = $statesStar;
        });

        return $this->matchingStatesCache[$cacheKey] = $states->states();
    }

    public function getAlphabet(): AlphabetInterface
    {
        return $this->alphabet;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getStateSet(): StateSetInterface
    {
        return $this->stateSet;
    }

    /**
     * Indexes an array of strings and returns an array where all strings have their state assigned.
     *
     * @return array<string, int>
     */
    public function index(array $strings): array
    {
        $assigned = [];

        foreach ($strings as $string) {
            // Seen this already, skip
            if (isset($this->indexCache[$string])) {
                $assigned[$string] = $this->indexCache[$string];
                continue;
            }

            $state = 0;
            $this->loopOverEveryCharacter($string, function (int $mappedChar) use (&$state) {
                $newState = (int) ($state * $this->config->getAlphabetSize() + $mappedChar);

                $this->stateSet->add($newState);
                $state = $newState;
            });

            $assigned[$string] = $this->indexCache[$string] = $state;
            $this->dataStore->add($state, $string);
        }

        return $assigned;
    }

    /**
     * Resets internal caches, use this in long-running processes in order to not run into a memory leak.
     */
    public function reset(): void
    {
        $this->indexCache = [];
        $this->matchingStatesCache = [];
        $this->trieFilters = [];
    }

    private function getReachableStates(int $startState, int $editDistance, int $currentDistance = 0): CostAnnotatedStateSet
    {
        $reachable = new CostAnnotatedStateSet();

        if ($currentDistance > $editDistance) {
            return $reachable;
        }

        // A state is always able to reach itself
        $reachable->add($startState, $currentDistance);

        if ($currentDistance >= $editDistance) {
            return $reachable;
        }

        for ($c = 1; $c <= $this->config->getAlphabetSize(); $c++) {
            $state = $startState * $this->config->getAlphabetSize() + $c;
            if ($this->stateSet->has($state)) {
                $reachable = $reachable->mergeWith($this->getReachableStates($state, $editDistance, $currentDistance + 1));
            }
        }

        return $reachable;
    }

    /**
     * @param \Closure(int) $closure
     */
    private function loopOverEveryCharacter(string $string, \Closure $closure): void
    {
        $indexedSubstringLength = min($this->config->getIndexLength(), mb_strlen($string));
        $indexedSubstring = mb_substr($string, 0, $indexedSubstringLength);

        foreach (mb_str_split($indexedSubstring) as $char) {
            $mappedChar = $this->alphabet->map($char, $this->config->getAlphabetSize());
            $closure($mappedChar);
        }
    }
}
