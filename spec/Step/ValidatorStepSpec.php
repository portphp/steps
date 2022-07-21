<?php

namespace spec\Port\Steps\Step;

use Port\Steps\Step;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
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

        $constraint->groups = [];

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

        $constraint->groups = [];

        $this->add('foo', $constraint)->shouldReturn($this);

        $this->process(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );

        $this->getViolations()->shouldReturn([1 => $list]);
    }

    function it_throws_an_exception_when_option_is_not_supported(Step $step, ValidatorInterface $validator, Constraint $constraint, ConstraintViolation $violation)
    {
        $next = function() {};
        $item = ['foo' => true];
        $step->process($item, $next)->shouldNotBeCalled();

        $constraint->groups = [];

        $this->add('foo', $constraint)->shouldReturn($this);
        $this->addOption('bar', 'baz')->shouldReturn($this);

        $this->shouldThrow('Symfony\Component\Validator\Exception\InvalidOptionsException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_throws_an_exception_during_process_when_validation_fails(
        Step $step,
        ValidatorInterface $validator,
        Constraint $constraint,
        ConstraintViolation $violation
    ) {
        $next = function() {};
        $item = ['foo' => true];
        $step->process($item, $next)->shouldNotBeCalled();
        $list = new ConstraintViolationList([$violation->getWrappedObject()]);
        $validator->validate($item, Argument::type('Symfony\Component\Validator\Constraints\Collection'))->willReturn($list);

        $constraint->groups = [];

        $this->add('foo', $constraint)->shouldReturn($this);
        $this->throwExceptions();

        $this->shouldThrow('Port\Exception\ValidationException')->duringProcess(
            $item,
            function($item) use ($step, $next) {
                return $step->process($item, $next);
            }
        );
    }

    function it_validates_an_item_from_metadata(
        ValidatorInterface $validator,
        ConstraintViolationListInterface $list
    ) {
        $next = function() {};
        $list->count()->willReturn(1);
        $item = new \stdClass();
        $validator->validate($item)->willReturn($list);

        $this->process(
            $item,
            $next
        );

        $this->getViolations()->shouldReturn([1 => $list]);
    }

    function it_validates_multiple_items_from_metadata(
        ValidatorInterface $validator,
        ConstraintViolationListInterface $list
    )
    {
        $numberOfCalls = 3;
        $next = function() {};
        $list->count()->willReturn(1);
        $item = new \stdClass();
        $validator->validate($item)->willReturn($list);

        for ($i = 0; $i < $numberOfCalls; $i++) {
            $this->process($item, $next);
        }

        $this->getViolations()->shouldReturn(array_fill(1, $numberOfCalls, $list));
    }

    function it_tracks_lines_when_exceptions_are_thrown_during_process(
        Step $step,
        ValidatorInterface $validator,
        Constraint $constraint,
        ConstraintViolation $violation
    )
    {
        $numberOfCalls = 3;
        $next = function() {};
        $errorList = new ConstraintViolationList([$violation->getWrappedObject()]);
        $stepFunc = function($item) use ($step, $next) {
            return $step->process($item, $next);
        };
        $item = ['foo' => 10];

        $constraint->groups = [];

        $validator->validate($item, Argument::type('Symfony\Component\Validator\Constraints\Collection'))
            ->willReturn($errorList);

        $step->process($item, $next)->shouldNotBeCalled();

        $this->throwExceptions();
        $this->add('foo', $constraint)->shouldReturn($this);

        for ($i = 0; $i < $numberOfCalls; $i++) {
            $this->shouldThrow('Port\Exception\ValidationException')->duringProcess($item, $stepFunc);
        }

        $this->getViolations()->shouldReturn(array_fill(1, $numberOfCalls, $errorList));
    }
}
