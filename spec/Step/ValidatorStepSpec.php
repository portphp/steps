<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ValidatorStepSpec extends ObjectBehavior
{
    function let(ValidatorInterface $validator)
    {
        $this->beConstructedWith($validator);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\Step\ValidatorStep');
    }

    function it_is_a_step()
    {
        $this->shouldHaveType('Port\Steps\Step');
    }

    function it_has_a_priority()
    {
        $this->getPriority()->shouldReturn(128);
    }

    function it_processes_an_item(Step $step, ValidatorInterface $validator, Constraint $constraint, ConstraintViolationListInterface $list)
    {
        $next = function() {};
        $item = ['foo' => true];
        $step->process($item, $next)->willReturn(true);
        $list->count()->willReturn(0);
        $validator->validate($item, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->add('foo', $constraint)->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_processes_and_validates_an_item(Step $step, ValidatorInterface $validator, Constraint $constraint, ConstraintViolationListInterface $list)
    {
        $next = function() {};
        $item = ['foo' => true];
        $step->process($item, $next)->shouldNotBeCalled();
        $list->count()->willReturn(1);
        $validator->validate($item, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->add('foo', $constraint)->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );

        $this->getViolations()->shouldReturn([1 => $list]);
    }

    function it_throws_an_exception_during_process_when_validation_fails(Step $step, ValidatorInterface $validator, Constraint $constraint, ConstraintViolationListInterface $list)
    {
        $next = function() {};
        $item = ['foo' => true];
        $step->process($item, $next)->shouldNotBeCalled();
        $list->count()->willReturn(1);
        $validator->validate($item, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $this->add('foo', $constraint)->shouldReturn($this);
        $this->throwExceptions();

        $this->shouldThrow('Port\Exception\ValidationException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }
}
