<?php

namespace Toflar\StateSetIndex\Test\Levenshtein;

use PHPUnit\Framework\TestCase;
use Toflar\StateSetIndex\Levenshtein\Automaton;

class AutomatonTest extends TestCase
{
    public function testAutomaton(): void
    {
        $automaton = new Automaton('foobar', 2);
        $startState = $automaton->start();

        $this->assertSame([[0, 1, 2], [0, 1, 2]], $startState);

        $newState = $automaton->step($startState, 'f');

        $this->assertSame([[0, 1, 2, 3], [1, 0, 1, 2]], $newState);

        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        $newState = $automaton->step($newState, 'o');

        $this->assertSame([[0, 1, 2, 3, 4], [2, 1, 0, 1, 2]], $newState);

        $this->assertTrue($automaton->canMatch($newState));
        $this->assertFalse($automaton->isMatch($newState));

        $newState = $automaton->step($newState, 'o');
        $newState = $automaton->step($newState, 'b');

        $this->assertTrue($automaton->canMatch($newState));
        $this->assertTrue($automaton->isMatch($newState));
    }
}