<?php

namespace Netflex\Query\Traits;

use Closure;

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
