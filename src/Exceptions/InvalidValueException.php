<?php

namespace Netflex\Query\Exceptions;

use Netflex\Query\Builder;

class InvalidValueException extends QueryBuilderException
{
  /**
   * @param string $value
   */
  public function __construct($value)
  {
    $type = gettype($value);
    parent::__construct("Unexpected value: $type, expected one of: [" . implode(', ', Builder::VALUE_TYPES) . "]");
  }
}
