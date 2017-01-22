<?php

namespace spec\Port\Steps\Step;

use Port\Filter\OffsetFilter;
use Port\Filter\ValidatorFilter;
use Port\Steps\Step;
use PhpSpec\ObjectBehavior;

class FilterStepSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\FilterStep');
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

    function it_processes_and_filters_an_item(Step $step)
    {
        $next = function() {};
        $item = [];
        $step->process($item, $next)->shouldNotBeCalled();

        $this->add(function($item) {
            return false;
        })->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_sorts_filters_by_priority(Step $step, DummyFilter $dummyFilter1, DummyFilter $dummyFilter2)
    {
        $next = function() {};
        $item = [];
        $step->process($item, $next)->shouldNotBeCalled();
        $dummyFilter1->__invoke($item)->willReturn(false);
        $dummyFilter2->__invoke($item)->shouldNotBeCalled();

        $this->add($dummyFilter1)
            ->add($dummyFilter2);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_allows_filter_priority_override(Step $step, DummyFilter $dummyFilter1, DummyFilter $dummyFilter2)
    {
        $next = function() {};
        $item = [];
        $step->process($item, $next)->shouldNotBeCalled();
        $dummyFilter1->__invoke($item)->willReturn(false);
        $dummyFilter2->__invoke($item)->shouldNotBeCalled();

        $this->add($dummyFilter1, 100)
            ->add($dummyFilter2);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}

class DummyFilter
{
    public function __invoke(array $item)
    {
        return true;
    }
}
