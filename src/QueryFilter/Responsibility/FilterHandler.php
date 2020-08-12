<?php

namespace eloquentFilter\QueryFilter\Responsibility;

use eloquentFilter\QueryFilter\Queries\QueryBuilder;

/**
 * Class FilterHandler
 * @package eloquentFilter\QueryFilter\Responsibility
 */
abstract class FilterHandler
{
    /**
     * @var FilterHandler|null
     */
    private $successor = null;
    /**
     * @var null
     */
    protected $queryBuilder = null;

    /**
     * FilterHandler constructor.
     * @param FilterHandler|null $handler
     */
    public function __construct(FilterHandler $handler = null)
    {
        $this->successor = $handler;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param $field
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    final public function handle(QueryBuilder $queryBuilder, $field, $arguments)
    {
        $this->queryBuilder = $queryBuilder;
        if ($this->handelListFields($field)) {
            $processed = $this->processing($field, $arguments);

            if ($processed === null && $this->successor !== null) {
                // the request has not been processed by this handler => see the next
                $processed = $this->successor->handle($this->queryBuilder, $field, $arguments);
            }

            return $processed;
        }
    }

    /**
     * @param string $field
     *
     * @throws \Exception
     *
     * @return bool
     */
    private function handelListFields(string $field)
    {
        if ($output = $this->checkSetWhiteListFields($field)) {
            return $output;
        } elseif ($field == 'f_params' || $field == 'or') {
            return true;
        } elseif ($this->checkModelHasOverrideMethod($field)) {
            return true;
        }

        $class_name = class_basename($this->queryBuilder->getBuilder()->getModel());

        throw new \Exception("You must set $field in whiteListFilter in $class_name.php
         or create a override method with name $field or call ignoreRequest function for ignore $field.");
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    protected function checkModelHasOverrideMethod(string $field): bool
    {
        if (method_exists($this->queryBuilder->getBuilder()->getModel(), $field)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    private function checkSetWhiteListFields(string $field): bool
    {
        if (in_array($field, $this->queryBuilder->getBuilder()->getModel()->getWhiteListFilter()) ||
            $this->queryBuilder->getBuilder()->getModel()->getWhiteListFilter()[0] == '*') {
            return true;
        }

        return false;
    }

    /**
     * @param $field
     * @param $arguments
     * @return mixed
     */
    abstract protected function processing($field, $arguments);
}
