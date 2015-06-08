<?php

namespace Port\Steps;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
interface Step
{
    /**
     * Any processing done on each item in the data stack
     *
     * @param mixed    $item
     * @param callable $next
     *
     * @return boolean False return value means the item is skipped
     */
    public function process($item, callable $next);
}
