<?php

declare(strict_types=1);

namespace Toflar\StateSetIndex\Test\Benchmark;

use Faker\Factory;
use PhpBench\Attributes as Bench;
use Toflar\StateSetIndex\Alphabet\Utf8Alphabet;
use Toflar\StateSetIndex\Config;
use Toflar\StateSetIndex\DataStore\InMemoryDataStore;
use Toflar\StateSetIndex\StateSet\InMemoryStateSet;
use Toflar\StateSetIndex\StateSetIndex;

#[Bench\BeforeMethods(['setUp'])]
#[Bench\Revs(1)]
#[Bench\Iterations(5)]
class StateSetIndexBench
{
    private StateSetIndex $index;

    /**
     * @var list<string>
     */
    private array $queries = [];

    public function setUp(): void
    {
        $faker = Factory::create();
        $faker->seed(42); // Seed for consistent benchmarks

        $this->index = new StateSetIndex(
            new Config(6, 8),
            new Utf8Alphabet(),
            new InMemoryStateSet(),
            new InMemoryDataStore(),
        );

        // Index 5000 words and query for 500
        $words = $faker->words(5_000);
        $this->index->index($words);
        $this->queries = \array_slice($words, 0, 500);
    }

    #[Bench\ParamProviders(['provideFindMatchingStatesCases'])]
    public function benchFindMatchingStates(array $params): void
    {
        foreach ($this->queries as $query) {
            $this->index->findMatchingStates($query, (int) $params['editDistance'], (int) $params['transpositionCost']);
        }
    }

    /**
     * @return array<string, array{editDistance:int, transpositionCost:int}>
     */
    public function provideFindMatchingStatesCases(): array
    {
        return [
            'single-edit' => [
                'editDistance' => 1,
                'transpositionCost' => 1,
            ],
            'double-edit-cost-1' => [
                'editDistance' => 2,
                'transpositionCost' => 1,
            ],
            'double-edit-cost-2' => [
                'editDistance' => 2,
                'transpositionCost' => 2,
            ],
        ];
    }
}
