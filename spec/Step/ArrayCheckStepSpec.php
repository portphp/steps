<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use PhpSpec\ObjectBehavior;

class ArrayCheckStepSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\ArrayCheckStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_processes_an_item(Step $step)
    {
        $next = function() {};
        $item = [];
        $step->process($item, $next)->willReturn(true);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_throws_an_exception_when_item_is_invalid(Step $step)
    {
        $next = function() {};
        $item = 'string';
        $step->process($item, $next)->shouldNotBeCalled();

        $this->shouldThrow('Port\Exception\UnexpectedTypeException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
