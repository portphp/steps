<?php

namespace Port\Steps\Step;

use Port\Exception\MappingException;
use Port\Steps\Step;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MappingStep implements Step
{
    /**
     * @var array
     */
    private $mappings = [];

    /**
     * @var PropertyAccessorInterface
     */
    private $accessor;

    /**
     * @param array                     $mappings
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(array $mappings = [], PropertyAccessorInterface $accessor = null)
    {
        $this->mappings = $mappings;
        $this->accessor = $accessor ?: new PropertyAccessor();
    }

    /**
     * @param string $from
     * @param string $to
     *
     * @return $this
     */
    public function map($from, $to)
    {
        $this->mappings[$from] = $to;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function process($item, callable $next)
    {
        try {
            foreach ($this->mappings as $from => $to) {
                $value = $this->accessor->getValue($item, $from);
                $this->accessor->setValue($item, $to, $value);

                $from = str_replace(['[',']'], '', $from);

                // Check if $item is an array, because properties can't be unset.
                // So we don't call unset for objects to prevent side affects.
                // Also, we don't have to unset the property if the key is the same
                if (is_array($item) && array_key_exists($from, $item) && $from !== str_replace(['[',']'], '', $to)) {
                    unset($item[$from]);
                }
            }
        } catch (NoSuchPropertyException $exception) {
            throw new MappingException('Unable to map item', 0, $exception);
        } catch (UnexpectedTypeException $exception) {
            throw new MappingException('Unable to map item', 0, $exception);
        }

        return $next($item);
    }
}
