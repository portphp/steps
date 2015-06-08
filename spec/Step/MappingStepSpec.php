<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use PhpSpec\ObjectBehavior;

class MappingStepSpec extends ObjectBehavior
{
    function let(PropertyAccessorInterface $propertyAccessor)
    {
        $this->beConstructedWith([], $propertyAccessor);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\MappingStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_processes_an_item(Step $step, PropertyAccessorInterface $propertyAccessor)
    {
        $next = function() {};
        // We cannot mock behavior by reference
        $item = ['foo' => true, 'bar' => true];
        $item2 = ['bar' => true];
        $step->process($item2, $next)->willReturn(true);
        $propertyAccessor->getValue($item, 'foo')->willReturn(true);
        $propertyAccessor->setValue($item, 'bar', true)->shouldBeCalled();

        $this->map('foo', 'bar')->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_throws_a_mapping_exception_when_no_property_is_found(Step $step, PropertyAccessorInterface $propertyAccessor)
    {
        $next = function() {};
        // We cannot mock behavior by reference
        $item = ['foo' => true, 'bar' => true];
        $item2 = ['bar' => true];
        $step->process($item2, $next)->shouldNotBeCalled();
        $propertyAccessor->getValue($item, 'foo')->willThrow('Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException');
        $propertyAccessor->setValue($item, 'bar', true)->shouldNotBeCalled();

        $this->map('foo', 'bar')->shouldReturn($this);

        $this->shouldThrow('Port\Exception\MappingException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_throws_a_mapping_exception_when_the_type_is_unexpected(Step $step, PropertyAccessorInterface $propertyAccessor)
    {
        $next = function() {};
        // We cannot mock behavior by reference
        $item = ['foo' => true, 'bar' => true];
        $item2 = ['bar' => true];
        $step->process($item2, $next)->shouldNotBeCalled();
        $propertyAccessor->getValue($item, 'foo')->willThrow('Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException');
        $propertyAccessor->setValue($item, 'bar', true)->shouldNotBeCalled();

        $this->map('foo', 'bar')->shouldReturn($this);

        $this->shouldThrow('Port\Exception\MappingException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
