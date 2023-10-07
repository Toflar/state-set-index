<?php

namespace Toflar\StateSetIndex\DataStore;

class InMemoryDataStore implements DataStoreInterface
{
    /**
     * @var array<int, array<string>>
     */
    private array $data = [];

    public function add(int $state, string $string): void
    {
        $this->data[$state][] = $string;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function getForStates(array $states = []): array
    {
        return array_intersect_key($this->data, array_flip($states));
    }
}
