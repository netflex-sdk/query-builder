<?php

namespace Netflex\Query\Exceptions;

use Netflex\Query\Builder;

class InvalidOperatorException extends QueryBuilderException
{
  /**
   * @param string $operator
   */
  public function __construct($operator)
  {
    parent::__construct("Unexpected operator $operator, expected one of: [" . implode(',', Builder::OPERATORS) . "]");
  }
}
