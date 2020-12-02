<?php

namespace Port\Steps\Step;

use ArrayAccess;
use Port\Exception\UnexpectedTypeException;
use Port\Steps\Step;
use Traversable;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class ArrayCheckStep implements Step
{
    /**
     * {@inheritdoc}
     */
    public function process($item, callable $next)
    {
        if (!is_array($item) && !($item instanceof ArrayAccess && $item instanceof Traversable)) {
            throw new UnexpectedTypeException($item, 'array');
        }

        return $next($item);
    }
}
