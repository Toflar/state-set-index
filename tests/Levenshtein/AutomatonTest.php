<?php

namespace Toflar\StateSetIndex\Test\Levenshtein;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Levenshtein\Automaton;
use Toflar\StateSetIndex\Levenshtein\TrieFilter;

class AutomatonTest extends TestCase
{
    public function testAutomaton(): void
    {
        $automaton = new Automaton('foobar', 2, 1, 1, 2, 1);

        $startState = $automaton->start();
        $this->assertSame([0 => 0], $startState);

        // Step with 'f' (match)
        $newState = $automaton->step($startState, 'f');
        $this->assertSame([0 => 1, 1 => 0], $newState);
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        // Step with 'o' (partial match)
        $newState = $automaton->step($newState, 'o');
        $this->assertSame([0 => 2, 1 => 1, 2 => 0, 3 => 1], $newState);
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        // Step with 'o' (continued match)
        $newState = $automaton->step($newState, 'o');
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        // Step with 'b' (near end of match)
        $newState = $automaton->step($newState, 'b');
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        // Step with 'a' (complete match)
        $newState = $automaton->step($newState, 'a');
        $newState = $automaton->step($newState, 'r'); // Final step
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertTrue($automaton->isMatch($newState));
    }

    public function testTransposition(): void
    {
        $automaton = new Automaton('foobar', 2, 1, 1, 2, 1);

        // Initial state
        $startState = $automaton->start();
        $this->assertSame([0 => 0], $startState);

        // Step with 'o' (transposition handling)
        $newState = $automaton->step($startState, 'o');
        $newState = $automaton->step($newState, 'f'); // Transposed letters
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));
    }

    public function testCosts(): void
    {
        $automaton = new Automaton('foobar', 3, 2, 1, 3, 1); // Custom costs

        $startState = $automaton->start();
        $this->assertSame([0 => 0], $startState);

        // Step with 'f' (match, cost 0)
        $newState = $automaton->step($startState, 'f');
        $this->assertSame([0 => 2, 1 => 0], $newState); // Insertion cost is now 2
        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        // Step with 'x' (replacement, cost 3)
        $newState = $automaton->step($newState, 'x');
        $this->assertFalse($automaton->isMatch($newState));
    }
}
