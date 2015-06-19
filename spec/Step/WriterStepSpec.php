<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use Port\Writer;
use PhpSpec\ObjectBehavior;

class WriterStepSpec extends ObjectBehavior
{
    function let(Writer $writer)
    {
        $this->beConstructedWith($writer);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\WriterStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_processes_an_item(Writer $writer, Step $step)
    {
        $next = function() {};
        $item = [];
        $step->process($item, $next)->willReturn(true);
        $writer->writeItem($item)->shouldBeCalled();

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
