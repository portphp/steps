<?php

namespace Port\Steps\Tests\Step;

use Port\Steps\Step\ValidatorStep;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\ConstraintViolationList;

class ValidatorStepTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\RecursiveValidator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->step = new ValidatorStep($this->validator);
    }

    public function testProcess()
    {
        $data = [
            'title' => null,
        ];

        $this->step->add('title', $constraint = new Constraints\NotNull());

        $this->validator->expects($this->once())
            ->method('validate')
            ->will($this->returnValue(
                $list = new ConstraintViolationList([
                    $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
                        ->disableOriginalConstructor()
                        ->getMock()
                ])
            ));

        $this->assertFalse($this->step->process($data));

        $this->assertEquals([1 => $list], $this->step->getViolations());
    }

    /**
     * @expectedException Port\Exception\ValidationException
     */
    public function testProcessWithExceptions()
    {
        $data = [
            'title' => null,
        ];

        $this->step->add('title', $constraint = new Constraints\NotNull());
        $this->step->throwExceptions();

        $this->validator->expects($this->once())
        ->method('validate')
        ->will($this->returnValue(
        $list = new ConstraintViolationList([
            $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock()
            ])
        ));

        $this->assertFalse($this->step->process($data));
    }

    public function testPriority()
    {
        $this->assertEquals(128, $this->step->getPriority());
    }
}
