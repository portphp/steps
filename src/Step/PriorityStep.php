<?php

namespace Port\Steps\Step;

use Port\Steps\Step;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
interface PriorityStep extends Step
{
    /**
     * @return integer
     */
    public function getPriority();
}
