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
    private $line = 0;

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
        if (!isset($this->constraints['fields'][$field])) {
            $this->constraints['fields'][$field] = [];
        }

        $this->constraints['fields'][$field][] = $constraint;

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
     * Add additional options to the Collection constraint.
     *
     * @param string $option
     * @param mixed  $optionValue
     *
     * @return $this
     */
    public function addOption($option, $optionValue)
    {
        $this->constraints[$option] = $optionValue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item, callable $next)
    {
        $this->line++;

        if (count($this->constraints) > 0) {
            $constraints = $this->createCollectionConstraint();
            $list = $this->validator->validate($item, $constraints);
        } else {
            $list = $this->validator->validate($item);
        }

        if (count($list) > 0) {
            $this->violations[$this->line] = $list;

            if ($this->throwExceptions) {
                throw new ValidationException($list, $this->line);
            }
        }

        if (0 === count($list)) {
            return $next($item);
        }
    }


    /**
     * Build a Collection constraint compatible with Symfony 5.4–8.
     *
     * Symfony 7+ uses a fields-first constructor; older versions use an options bag.
     */
    private function createCollectionConstraint(): Constraints\Collection
    {
        $fields = $this->constraints['fields'] ?? [];
        $options = $this->constraints;
        unset($options['fields']);

        $constructor = (new \ReflectionClass(Constraints\Collection::class))->getConstructor();
        $parameters = $constructor ? $constructor->getParameters() : [];
        $first = $parameters[0] ?? null;

        // Symfony 7+/8: first argument is $fields (field map), not an options array
        if ($first && $first->getName() === 'fields') {
            $allowed = [];
            foreach ($parameters as $parameter) {
                $allowed[$parameter->getName()] = true;
            }
            $named = ['fields' => $fields];
            foreach ($options as $name => $value) {
                if (!isset($allowed[$name])) {
                    throw new \Symfony\Component\Validator\Exception\InvalidOptionsException(
                        sprintf('The option "%s" does not exist in constraint "%s".', $name, Constraints\Collection::class),
                        [$name]
                    );
                }
                $named[$name] = $value;
            }

            return new Constraints\Collection(...$named);
        }

        return new Constraints\Collection(array_merge(['fields' => $fields], $options));
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 128;
    }
}
