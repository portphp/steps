<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use PhpSpec\ObjectBehavior;

class ConverterStepSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\ConverterStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_processes_an_item(Step $step)
    {
        $converter = function($item) {
            return $item;
        };
        $this->beConstructedWith([$converter]);

        $next = function() {};
        $item = [];
        $item2 = ['item2'];
        $step->process($item2, $next)->willReturn(true);

        $this->add(function($item) use($item2) {
            return $item2;
        });

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
