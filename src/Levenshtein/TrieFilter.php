<?php

namespace Toflar\StateSetIndex\Levenshtein;

class TrieFilter
{
    private array $trie = [
        'children' => [],
        'canMatch' => null,
    ];

    public function __construct(
        private Automaton $automaton
    ) {
    }

    public function filterStrings(array $strings): array
    {
        $matches = [];

        foreach ($strings as $key => $string) {
            $state = $this->automaton->start();
            $chars = mb_str_split($string);
            $node = &$this->trie;

            foreach ($chars as $char) {
                if (isset($node['children'][$char])) {
                    $node = &$node['children'][$char];
                    $state = $this->automaton->step($state, $char);

                    if ($node['canMatch'] === false) {
                        continue 2; // Skip to the next string
                    }
                } else {
                    $state = $this->automaton->step($state, $char);
                    $canMatch = $this->automaton->canMatch($state);

                    $node['children'][$char] = [
                        'children' => [],
                        'canMatch' => $canMatch,
                    ];
                    $node = &$node['children'][$char];

                    if (!$canMatch) {
                        continue 2; // Skip to the next string
                    }
                }
            }

            if ($this->automaton->isMatch($state)) {
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
