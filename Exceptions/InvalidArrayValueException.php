<?php

namespace Netflex\Query\Exceptions;

use Netflex\Query\Builder;

class InvalidArrayValueException extends QueryBuilderException
{
    /**
     * @param string $value
     */
    public function __construct()
    {
        parent::__construct('Cannot use empty array as value.');
    }
}
