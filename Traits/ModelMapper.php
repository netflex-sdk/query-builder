<?php

namespace Netflex\Query\Traits;

use Closure;
use Netflex\Query\QueryableModel;

trait ModelMapper
{
  /**
   * @return Closure
   */
  protected function getMapper()
  {
    /** @var QueryableModel $this */
    return function ($attributes) {
      return (new static)->newFromBuilder($attributes);
    };
  }
}
