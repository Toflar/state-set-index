<?php

namespace Toflar\StateSetIndex;

final class MatchingStatesSnapshot
{
    /**
     * @param array<int, int> $states
     * @param array<int, array<int, int>> $lastSubstitutions
     */
    public function __construct(
        public readonly string $processedPrefix,
        public readonly int $editDistance,
        public readonly int $transpositionCost,
        public readonly int $cutOffLowerBound,
        public readonly array $states,
        public readonly array $lastSubstitutions,
        public readonly ?int $lastMappedChar,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (
            !isset($data['processedPrefix'], $data['editDistance'], $data['transpositionCost'], $data['cutOffLowerBound'], $data['states'], $data['lastSubstitutions'])
            || !\is_string($data['processedPrefix'])
            || !\is_int($data['editDistance'])
            || !\is_int($data['transpositionCost'])
            || !\is_int($data['cutOffLowerBound'])
            || !\is_array($data['states'])
            || !\is_array($data['lastSubstitutions'])
        ) {
            throw new \InvalidArgumentException('Invalid matching states snapshot payload.');
        }

        $lastMappedChar = $data['lastMappedChar'] ?? null;
        if ($lastMappedChar !== null && !\is_int($lastMappedChar)) {
            throw new \InvalidArgumentException('Invalid matching states snapshot payload.');
        }

        /** @var array<int, int> $states */
        $states = $data['states'];
        /** @var array<int, array<int, int>> $lastSubstitutions */
        $lastSubstitutions = $data['lastSubstitutions'];

        return new self(
            $data['processedPrefix'],
            $data['editDistance'],
            $data['transpositionCost'],
            $data['cutOffLowerBound'],
            $states,
            $lastSubstitutions,
            $lastMappedChar,
        );
    }

    public function matchesPrefix(string $term): bool
    {
        return mb_substr($term, 0, mb_strlen($this->processedPrefix)) === $this->processedPrefix;
    }

    /**
     * @return array<int>
     */
    public function matchingStates(): array
    {
        return array_keys($this->states);
    }

    /**
     * @return array{
     *     processedPrefix: string,
     *     editDistance: int,
     *     transpositionCost: int,
     *     cutOffLowerBound: int,
     *     states: array<int, int>,
     *     lastSubstitutions: array<int, array<int, int>>,
     *     lastMappedChar: int|null
     * }
     */
    public function toArray(): array
    {
        return [
            'processedPrefix' => $this->processedPrefix,
            'editDistance' => $this->editDistance,
            'transpositionCost' => $this->transpositionCost,
            'cutOffLowerBound' => $this->cutOffLowerBound,
            'states' => $this->states,
            'lastSubstitutions' => $this->lastSubstitutions,
            'lastMappedChar' => $this->lastMappedChar,
        ];
    }
}
