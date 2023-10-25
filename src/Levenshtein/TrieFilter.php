<?php

namespace Toflar\StateSetIndex\Levenshtein;

class TrieFilter
{
    /**
     * @var array<string, bool>
     */
    private array $trie = [];

    public function __construct(
        private Automaton $automaton
    ) {
    }

    /**
     * The keys are kept when filtering, so you can use that for e.g. ID assignment or anything else.
     *
     * @param array<string|int, string> $strings
     * @return array<string|int, string>
     */
    public function filterStrings(array $strings): array
    {
        $matches = [];

        foreach ($strings as $key => $string) {
            $state = $this->automaton->start();
            $chars = mb_str_split($string);
            $prefix = '';

            while ([] !== $chars) {
                $char = array_shift($chars);
                $prefix .= $char;

                // Cannot match, drop the string
                if (isset($this->trie[$prefix]) && $this->trie[$prefix] === false) {
                    continue 2; // Next string
                }

                // Next state for the next character
                $state = $this->automaton->step($state, $char);

                // Remember if that prefix can be matched or not to allow pruning the tree
                $this->trie[$prefix] = $this->automaton->canMatch($state);

                if (!$this->trie[$prefix]) {
                    continue 2; // Next string
                }
            }

            // Match
            if (true === $this->trie[$prefix]) {
                $matches[$key] = $string;
            }
        }

        return $matches;
    }

    public function matches(string $string): bool
    {
        return [] !== $this->filterStrings([$string]);
    }
}
