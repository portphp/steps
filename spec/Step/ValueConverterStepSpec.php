<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use PhpSpec\ObjectBehavior;

class ValueConverterStepSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\ValueConverterStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_processes_an_item(Step $step)
    {
        $next = function() {};
        $item = ['foo' => 'bar'];
        $item2 = ['foo' => 'baz'];
        $step->process($item2, $next)->willReturn(true);

        $this->add('[foo]', function($item) use($item2) {
            return $item2;
        })->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
