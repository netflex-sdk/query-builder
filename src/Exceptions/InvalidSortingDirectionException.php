<?php

namespace Netflex\Query\Exceptions;

use Netflex\Query\Builder;

class InvalidSortingDirectionException extends QueryBuilderException
{
  /**
   * @param string $direction
   */
  public function __construct($direction)
  {
    parent::__construct("Unexpected sorting direction: $direction, expected one of: [" . implode(',', Builder::SORTING_DIRS) . "]");
  }
}
