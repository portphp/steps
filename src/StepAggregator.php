<?php

namespace Port\Steps;

use Port\Exception;
use Port\Reader;
use Port\Result;
use Port\Steps\Step\PriorityStep;
use Port\Workflow;
use Port\Writer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Seld\Signal\SignalHandler;

/**
 * A mediator between a reader and one or more writers and converters
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class StepAggregator implements Workflow, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * Identifier for the Import/Export
     *
     * @var string|null
     */
    private $name = null;

    /**
     * @var boolean
     */
    private $skipItemOnFailure = false;

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var Writer[]
     */
    private $writers = [];

    /**
     * @param Reader $reader
     * @param string $name
     */
    public function __construct(Reader $reader, $name = null)
    {
        $this->name = $name;
        $this->reader = $reader;

        // Defaults
        $this->logger = new NullLogger();
    }

    /**
     * Add a step to the current workflow
     *
     * @param Step         $step
     * @param integer|null $priority
     *
     * @return $this
     */
    public function addStep(Step $step, $priority = null)
    {
        $priority = null === $priority && $step instanceof PriorityStep ? $step->getPriority() : $priority;
        $priority = null === $priority ? 0 : $priority;

        $this->steps[$priority][] = $step;

        return $this;
    }

    /**
     * Add a new writer to the current workflow
     *
     * @param Writer $writer
     *
     * @return $this
     */
    public function addWriter(Writer $writer)
    {
        array_push($this->writers, $writer);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $count      = 0;
        $exceptions = new \SplObjectStorage();
        $startTime  = new \DateTime;

        $signal = SignalHandler::create(['SIGTERM', 'SIGINT'], $this->logger);

        foreach ($this->writers as $writer) {
            $writer->prepare();
        }

        $pipeline = $this->buildPipeline();

        // Read all items
        foreach ($this->reader as $index => $item) {
            try {
                if ($signal->isTriggered()) {
                    break;
                }

                if (false === $pipeline($item)) {
                    continue;
                }
            } catch(Exception $e) {
                if (!$this->skipItemOnFailure) {
                    throw $e;
                }

                $exceptions->attach($e, $index);
                $this->logger->error($e->getMessage());
            }

            $count++;
        }

        foreach ($this->writers as $writer) {
            $writer->finish();
        }

        return new Result($this->name, $startTime, new \DateTime, $count, $exceptions);
    }

    /**
     * Sets the value which determines whether the item should be skipped when error occures
     *
     * @param boolean $skipItemOnFailure When true skip current item on process exception and log the error
     *
     * @return $this
     */
    public function setSkipItemOnFailure($skipItemOnFailure)
    {
        $this->skipItemOnFailure = $skipItemOnFailure;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Builds the pipeline
     *
     * @return callable
     */
    private function buildPipeline()
    {
        $nextCallable = function ($item) {
            // the final callable is a no-op
        };

        foreach ($this->getStepsSortedDescByPriority() as $step) {
            $nextCallable = function ($item) use ($step, $nextCallable) {
                return $step->process($item, $nextCallable);
            };
        }

        return $nextCallable;
    }

    /**
     * Sorts the internal list of steps and writers by priority in reverse order.
     *
     * @return Step[]
     */
    private function getStepsSortedDescByPriority()
    {
        $steps = $this->steps;
        // Use illogically large and small priorities
        $steps[-255][] = new Step\ArrayCheckStep;
        foreach ($this->writers as $writer) {
            $steps[-256][] = new Step\WriterStep($writer);
        }

        krsort($steps);

        $sortedStep = [];
        /** @var Step[] $stepsAtSamePriority */
        foreach ($steps as $stepsAtSamePriority) {
            foreach ($stepsAtSamePriority as $step) {
                $sortedStep[] = $step;
            }
        }

        return array_reverse($sortedStep);
    }
}
