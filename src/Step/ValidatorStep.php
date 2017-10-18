<?php

namespace Port\Steps\Step;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Port\Exception\ValidationException;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class ValidatorStep implements PriorityStep
{
    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var array
     */
    private $violations = [];

    /**
     * @var boolean
     */
    private $throwExceptions = false;

    /**
     * @var integer
     */
    private $line = 1;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param string     $field
     * @param Constraint $constraint
     *
     * @return $this
     */
    public function add($field, Constraint $constraint)
    {
        if (!isset($this->constraints[$field])) {
            $this->constraints[$field] = [];
        }

        $this->constraints[$field][] = $constraint;

        return $this;
    }

    /**
     * @param boolean $flag
     */
    public function throwExceptions($flag = true)
    {
        $this->throwExceptions = $flag;
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item, callable $next)
    {
        $constraints = new Constraints\Collection($this->constraints);
        $list = $this->validator->validate($item, $constraints);

        if (count($list) > 0) {
            $this->violations[$this->line] = $list;

            if ($this->throwExceptions) {
                $errorLine = $this->line;
                $this->line++;

                throw new ValidationException($list, $errorLine);
            }
        }

        $this->line++;

        if (0 === count($list)) {
            return $next($item);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 128;
    }
}
