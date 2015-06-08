<?php

namespace Port\Steps\Step;

use Port\Steps\Step;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class FilterStep implements Step
{
    /**
     * @var \SplPriorityQueue
     */
    private $filters;

    public function __construct()
    {
        $this->filters = new \SplPriorityQueue();
    }

    /**
     * @param callable $filter
     * @param integer  $priority
     *
     * @return $this
     */
    public function add(callable $filter, $priority = null)
    {
        $this->filters->insert($filter, $priority);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item, callable $next)
    {
        foreach (clone $this->filters as $filter) {
            if (false === call_user_func($filter, $item)) {
                return false;
            }
        }

        return $next($item);
    }
}
