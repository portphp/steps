<?php

namespace Port\Steps\Step;

use Port\Steps\Exception\BreakException;
use Port\Steps\Step;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class PcntlStep implements Step
{
    /**
     * Checks if PCNTL extension is available
     *
     * @var boolean
     */
    private $isPcntlAvailable = false;

    /**
     * @var boolean
     */
    private $shouldStop = false;

    public function __construct()
    {
        if (is_callable('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'stop']);
            pcntl_signal(SIGINT, [$this, 'stop']);
        }

        $this->isPcntlAvailable = is_callable('pcntl_signal_dispatch');
    }

    /**
     * {@inheritdoc}
     */
    public function process($item, callable $next)
    {
        if ($this->isPcntlAvailable) {
            pcntl_signal_dispatch();
        }

        if ($this->shouldStop) {
            throw new BreakException();
        }

        return $next($item);
    }

    /**
     * Stops processing and force return Result from process() function
     */
    public function stop()
    {
        $this->shouldStop = true;
    }
}
