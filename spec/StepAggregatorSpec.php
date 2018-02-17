<?php

namespace spec\Port\Steps;

use Port\Exception;
use Port\Reader;
use Port\Reader\ArrayReader;
use Port\Steps\Step;
use Port\Writer;
use Psr\Log\LoggerInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class StepAggregatorSpec extends ObjectBehavior
{
    function let(Reader $reader)
    {
        $this->beConstructedWith($reader);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Port\Steps\StepAggregator');
    }

    function it_adds_a_step(Step $step)
    {
        $this->addStep($step)->shouldReturn($this);
    }

    function it_adds_a_writer(Writer $writer)
    {
        $this->addWriter($writer)->shouldReturn($this);
    }

    function it_processes_a_workflow(Writer $writer)
    {
        $this->beConstructedWith($this->getReader(), 'Test workflow');

        $writer->prepare()->shouldBeCalled();
        $writer->writeItem(Argument::type('array'))->shouldBeCalledTimes(3);
        $writer->finish()->shouldBeCalled();

        $this->addWriter($writer);

        $result = $this->process();

        $result->shouldHaveType('Port\Result');
        $result->getStartTime()->shouldHaveType('DateTime');
        $result->getEndTime()->shouldHaveType('DateTime');
        $result->getElapsed()->shouldHaveType('DateInterval');
        $result->getTotalProcessedCount()->shouldReturn(3);
        $result->getSuccessCount()->shouldReturn(3);
        $result->getErrorCount()->shouldReturn(0);
        $result->hasErrors()->shouldReturn(false);
        $result->getExceptions()->shouldHaveType('SplObjectStorage');
        $result->getName()->shouldReturn('Test workflow');
    }

    function it_catches_exception(Writer $writer, LoggerInterface $logger)
    {
        $this->beConstructedWith($this->getReader());

        $e1 = new DummyException('Error 1');
        $e2 = new DummyException('Error 2');
        $e3 = new DummyException('Error 3');
        $writer->prepare()->shouldBeCalled();
        $writer->writeItem(['first' => 'James', 'last'  => 'Bond'])->willThrow($e1);
        $writer->writeItem(['first' => 'Miss', 'last'  => 'Moneypenny'])->willThrow($e2);
        $writer->writeItem(['first' => null, 'last'  => 'Doe'])->willThrow($e3);
        $writer->finish()->shouldBeCalled();
        $logger->error('Error 1')->shouldBeCalled();
        $logger->error('Error 2')->shouldBeCalled();
        $logger->error('Error 3')->shouldBeCalled();

        $this->addWriter($writer);
        $this->setLogger($logger);
        $this->setSkipItemOnFailure(true);

        $result = $this->process();

        $result->getTotalProcessedCount()->shouldReturn(3);
        $result->getSuccessCount()->shouldReturn(0);
        $result->getErrorCount()->shouldReturn(3);
        $result->hasErrors()->shouldReturn(true);
        $exceptions = $result->getExceptions();
        $exceptions->contains($e1)->shouldReturn(true);
        $exceptions->contains($e2)->shouldReturn(true);
        $exceptions->contains($e3)->shouldReturn(true);
    }

    function it_throws_an_exception_during_process(Writer $writer)
    {
        $this->beConstructedWith($this->getReader());

        $e = new DummyException('Error');
        $writer->prepare()->shouldBeCalled();
        $writer->writeItem(Argument::type('array'))->willThrow($e);
        $writer->finish()->shouldNotBeCalled();

        $this->addWriter($writer);

        $this->shouldThrow($e)->duringProcess();
    }

    function it_executes_the_writers_in_the_same_order_that_the_insertion(Writer $writerFoo, Writer $writerBar, Writer $writerBaz)
    {
        $data = '';
        $this->beConstructedWith(new ArrayReader([['test' => 'test']]));
        $writerFoo->prepare()->shouldBeCalled();
        $writerBar->prepare()->shouldBeCalled();
        $writerBaz->prepare()->shouldBeCalled();
        $writerFoo->writeItem(Argument::type('array'))->will(function () use ($writerBar, $writerBaz) {
            $writerBar->writeItem(Argument::type('array'))->will(function () use ($writerBaz) {
                $writerBaz->writeItem(Argument::type('array'))->shouldBeCalled();
            })->shouldBeCalled();
        })->shouldBeCalled();
        $writerFoo->finish()->shouldBeCalled();
        $writerBar->finish()->shouldBeCalled();
        $writerBaz->finish()->shouldBeCalled();

        $this->addWriter($writerFoo);
        $this->addWriter($writerBar);
        $this->addWriter($writerBaz);

        $this->process();
    }

    protected function getReader()
    {
        return new ArrayReader([
            [
                'first' => 'James',
                'last'  => 'Bond',
            ],
            [
                'first' => 'Miss',
                'last'  => 'Moneypenny',
            ],
            [
                'first' => null,
                'last'  => 'Doe',
            ],
        ]);
    }
}

class DummyException extends \Exception implements Exception
{

}
